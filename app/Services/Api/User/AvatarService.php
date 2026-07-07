<?php

namespace App\Services\Api\User;

use App\Models\User\Member;
use App\Models\User\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\Process\Process;

class AvatarService
{
    private const OUTPUT_SIZE = 512;

    private const MAX_DIMENSION = 3000;

    private const MAX_GIF_FRAMES = 300;

    /**
     * 初始化头像处理服务。
     *
     * @param AvatarUrlService $avatarUrlService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function __construct(private readonly AvatarUrlService $avatarUrlService)
    {
    }

    /**
     * 裁剪并保存当前用户头像。
     *
     * @param User $user
     * @param UploadedFile $file
     * @param array $crop
     * @return User
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    public function update(User $user, UploadedFile $file, array $crop): User
    {
        $extension = $this->extensionForMime($file->getMimeType());
        [$width, $height, $frames] = $this->inspect($file, $extension);
        $this->assertImageLimits($width, $height, $frames);
        $this->assertCropBounds($width, $height, $crop);

        $processingPath = $this->processingPath($extension);
        $relativePath = '/uploads/avatars/'.date('Ymd').'/'.Str::uuid().'.'.$extension;
        $absolutePath = public_path($relativePath);
        $oldPath = $user->member?->avatar;

        try {
            $this->crop($file->getRealPath(), $processingPath, $extension, $crop);
            $this->moveToPublicDirectory($processingPath, $absolutePath);

            try {
                DB::transaction(function () use ($user, $relativePath) {
                    $member = $user->member ?: new Member(['user_id' => (string) $user->id]);
                    $member->avatar = $relativePath;
                    $member->user_id = (string) $user->id;
                    $member->edit();
                });
            } catch (\Throwable $exception) {
                $this->deleteFile($absolutePath);
                throw $exception;
            }

            if ($oldPath && $oldPath !== $relativePath) {
                $this->deleteOldAvatar($oldPath);
            }
        } finally {
            if (is_file($processingPath) && ! unlink($processingPath)) {
                throw new RuntimeException('头像临时文件清理失败');
            }
        }

        $profile = User::query()->with(['member', 'roles'])->findOrFail($user->id);
        $profile->member->avatar = $this->avatarUrlService->make($relativePath);

        return $profile;
    }

    /**
     * 根据真实 MIME 获取输出扩展名。
     *
     * @param string|null $mime
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function extensionForMime(?string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            default => throw ValidationException::withMessages(['file' => '头像文件格式不正确']),
        };
    }

    /**
     * 读取图像尺寸与帧数。
     *
     * @param UploadedFile $file
     * @param string $extension
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function inspect(UploadedFile $file, string $extension): array
    {
        $command = $extension === 'gif'
            ? ['identify', '-format', '%w %h %n\n', $file->getRealPath()]
            : ['convert', $file->getRealPath(), '-auto-orient', '-format', '%w %h 1\n', 'info:'];
        $process = new Process($command);
        $process->mustRun();

        $line = strtok(trim($process->getOutput()), "\n");
        if (! is_string($line) || ! preg_match('/^(\d+) (\d+) (\d+)$/', trim($line), $matches)) {
            throw new RuntimeException('无法读取头像图像信息');
        }

        return [(int) $matches[1], (int) $matches[2], (int) $matches[3]];
    }

    /**
     * 校验图像尺寸与 GIF 帧数。
     *
     * @param int $width
     * @param int $height
     * @param int $frames
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function assertImageLimits(int $width, int $height, int $frames): void
    {
        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw ValidationException::withMessages(['file' => '头像尺寸不能超过 3000×3000 像素']);
        }

        if ($frames > self::MAX_GIF_FRAMES) {
            throw ValidationException::withMessages(['file' => 'GIF 头像不能超过 300 帧']);
        }
    }

    /**
     * 校验裁剪区域没有超出原图。
     *
     * @param int $width
     * @param int $height
     * @param array $crop
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function assertCropBounds(int $width, int $height, array $crop): void
    {
        if ($width < $crop['crop_x'] + $crop['crop_size']
            || $height < $crop['crop_y'] + $crop['crop_size']) {
            throw ValidationException::withMessages(['crop_size' => '头像裁剪区域超出原图范围']);
        }
    }

    /**
     * 生成头像处理临时路径。
     *
     * @param string $extension
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function processingPath(string $extension): string
    {
        $directory = storage_path('app/avatar-processing');
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('头像临时目录创建失败');
        }

        return $directory.'/'.Str::uuid().'.'.$extension;
    }

    /**
     * 执行头像裁剪与缩放。
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @param string $extension
     * @param array $crop
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function crop(string $sourcePath, string $targetPath, string $extension, array $crop): void
    {
        $geometry = sprintf(
            '%dx%d+%d+%d',
            $crop['crop_size'],
            $crop['crop_size'],
            $crop['crop_x'],
            $crop['crop_y']
        );

        $command = $extension === 'gif'
            ? [
                'convert', $sourcePath, '-coalesce', '-crop', $geometry, '+repage',
                '-resize', self::OUTPUT_SIZE.'x'.self::OUTPUT_SIZE.'>', '-layers', 'Optimize', $targetPath,
            ]
            : [
                'convert', $sourcePath, '-auto-orient', '-crop', $geometry, '+repage',
                '-resize', self::OUTPUT_SIZE.'x'.self::OUTPUT_SIZE.'!', $targetPath,
            ];

        (new Process($command))->mustRun();

        if (! is_file($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException('头像处理结果为空');
        }
    }

    /**
     * 将处理结果移动到头像公开目录。
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function moveToPublicDirectory(string $sourcePath, string $targetPath): void
    {
        $directory = dirname($targetPath);
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('头像目录创建失败');
        }

        if (! rename($sourcePath, $targetPath)) {
            throw new RuntimeException('头像文件保存失败');
        }
    }

    /**
     * 删除旧的本地头像。
     *
     * @param string $relativePath
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function deleteOldAvatar(string $relativePath): void
    {
        if (! str_starts_with($relativePath, '/uploads/avatars/')) {
            return;
        }

        $this->deleteFile(public_path($relativePath));
    }

    /**
     * 删除指定文件。
     *
     * @param string $path
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/7/7
     */
    private function deleteFile(string $path): void
    {
        if (is_file($path) && ! unlink($path)) {
            throw new RuntimeException('头像文件删除失败');
        }
    }
}
