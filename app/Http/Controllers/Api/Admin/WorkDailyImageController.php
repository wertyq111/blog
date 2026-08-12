<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkDailyImageController extends Controller
{
    /**
     * 上传 Markdown 正文图片，返回可公开访问的绝对地址。
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     *
     * @date 2026/8/12
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/gif,image/webp', 'max:5120'],
        ]);

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if ($extension === '') {
            $extension = match ($file->getMimeType()) {
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
                default => '',
            };
        }
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

        if (!in_array($extension, $allowedExtensions, true)) {
            return response()->json(message('文件格式不正确', false));
        }

        $relativeDir = '/uploads/work-daily/' . date('Ymd');
        $targetDir = public_path($relativeDir);

        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('上传目录创建失败');
        }

        $fileName = Str::uuid()->toString() . '.' . $extension;
        $file->move($targetDir, $fileName);

        $path = $relativeDir . '/' . $fileName;

        // 不能用 url()：前端 dev server 代理会把 Host 改写成容器内地址（host.docker.internal），
        // 生成的图片地址浏览器打不开。统一按 APP_URL 拼，和头像地址口径一致。
        $baseUrl = rtrim((string) config('app.url'), '/');
        if ($baseUrl === '') {
            throw new \RuntimeException('APP_URL 未配置');
        }

        return response()->json(message(MESSAGE_OK, true, [
            'url' => $baseUrl . $path,
            'path' => $path,
        ]));
    }
}
