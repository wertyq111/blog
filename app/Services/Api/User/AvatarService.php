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

    private const ANIMATED_EXTENSIONS = ['gif', 'mp4'];

    private const MAX_ANIMATED_SECONDS = 10;

    private const MAX_ANIMATED_FPS = 24;

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
     * @date 2026/10/9
     */
    public function update(User $user, UploadedFile $file, array $crop): User
    {
        $sourceExtension = $this->extensionForMime($file->getMimeType());
        $animated = in_array($sourceExtension, self::ANIMATED_EXTENSIONS, true);
        [$width, $height, $fps] = $animated ? $this->inspectAnimated($file) : [...$this->inspect($file), 0];
        $this->assertImageLimits($width, $height);
        $this->assertCropBounds($width, $height, $crop);

        // GIF / MP4 统一转成 WebP 动图，静态图保持原格式
        $extension = $animated ? 'webp' : $sourceExtension;
        $processingPath = $this->processingPath($extension);
        $relativePath = '/uploads/avatars/'.date('Ymd').'/'.Str::uuid().'.'.$extension;
        $absolutePath = public_path($relativePath);
        $oldPath = $user->member?->avatar;

        try {
            $animated
                ? $this->transcode($file->getRealPath(), $processingPath, $crop, $fps)
                : $this->crop($file->getRealPath(), $processingPath, $crop);
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
     * 根据真实 MIME 获取上传文件的扩展名。
     *
     * @param string|null $mime
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function extensionForMime(?string $mime): string
    {
        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'video/mp4' => 'mp4',
            default => throw ValidationException::withMessages(['file' => '头像文件格式不正确']),
        };
    }

    /**
     * 读取静态图像尺寸。
     *
     * @param UploadedFile $file
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function inspect(UploadedFile $file): array
    {
        $process = new Process(['convert', $file->getRealPath(), '-auto-orient', '-format', '%w %h\n', 'info:']);
        $process->mustRun();

        $line = strtok(trim($process->getOutput()), "\n");
        if (! is_string($line) || ! preg_match('/^(\d+) (\d+)$/', trim($line), $matches)) {
            throw new RuntimeException('无法读取头像图像信息');
        }

        return [(int) $matches[1], (int) $matches[2]];
    }

    /**
     * 读取 GIF / MP4 的画面尺寸与帧率。
     *
     * @param UploadedFile $file
     * @return array
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function inspectAnimated(UploadedFile $file): array
    {
        $process = new Process([
            'ffprobe', '-v', 'error', '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height,avg_frame_rate',
            '-of', 'csv=p=0', $file->getRealPath(),
        ]);
        $process->mustRun();

        $line = strtok(trim($process->getOutput()), "\n");
        if (! is_string($line) || ! preg_match('#^(\d+),(\d+),(\d+)/(\d+)#', trim($line), $matches)) {
            throw ValidationException::withMessages(['file' => '头像文件里没有可用的画面']);
        }

        $fps = (int) $matches[4] > 0 ? (int) $matches[3] / (int) $matches[4] : 0;

        return [(int) $matches[1], (int) $matches[2], $fps];
    }

    /**
     * 校验图像尺寸。
     *
     * @param int $width
     * @param int $height
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function assertImageLimits(int $width, int $height): void
    {
        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION) {
            throw ValidationException::withMessages(['file' => '头像尺寸不能超过 3000×3000 像素']);
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
     * 裁剪静态头像并缩放到固定尺寸。
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @param array $crop
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function crop(string $sourcePath, string $targetPath, array $crop): void
    {
        $geometry = sprintf(
            '%dx%d+%d+%d',
            $crop['crop_size'],
            $crop['crop_size'],
            $crop['crop_x'],
            $crop['crop_y']
        );

        (new Process([
            'convert', $sourcePath, '-auto-orient', '-crop', $geometry, '+repage',
            '-resize', self::OUTPUT_SIZE.'x'.self::OUTPUT_SIZE.'!', $targetPath,
        ]))->mustRun();

        $this->assertProcessed($targetPath);
    }

    /**
     * 把 GIF / MP4 裁剪并转成 WebP 动图。
     *
     * @param string $sourcePath
     * @param string $targetPath
     * @param array $crop
     * @param float $fps
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function transcode(string $sourcePath, string $targetPath, array $crop, float $fps): void
    {
        $filters = [sprintf('crop=%1$d:%1$d:%2$d:%3$d', $crop['crop_size'], $crop['crop_x'], $crop['crop_y'])];
        if ($fps > self::MAX_ANIMATED_FPS) {
            $filters[] = 'fps='.self::MAX_ANIMATED_FPS;
        }
        // 只缩不放：裁剪结果不足 512 时保持原尺寸，放大只会变糊变大
        if ($crop['crop_size'] > self::OUTPUT_SIZE) {
            $filters[] = sprintf('scale=%1$d:%1$d:flags=lanczos', self::OUTPUT_SIZE);
        }

        $process = new Process([
            'ffmpeg', '-v', 'error', '-y', '-i', $sourcePath,
            '-t', (string) self::MAX_ANIMATED_SECONDS, '-an',
            '-vf', implode(',', $filters),
            '-c:v', 'libwebp_anim', '-q:v', '75', '-compression_level', '4', '-loop', '0',
            $targetPath,
        ]);
        // 10 秒 720p 素材实测转码约 17 秒，Process 默认 60 秒超时余量不够；前端该接口超时是 120 秒
        $process->setTimeout(100);
        $process->mustRun();

        $this->assertProcessed($targetPath);
    }

    /**
     * 确认头像处理结果已生成。
     *
     * @param string $path
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/10/9
     */
    private function assertProcessed(string $path): void
    {
        if (! is_file($path) || filesize($path) === 0) {
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
