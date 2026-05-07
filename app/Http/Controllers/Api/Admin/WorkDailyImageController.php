<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WorkDailyImageController extends Controller
{
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

        return response()->json(message(MESSAGE_OK, true, [
            'url' => url($path),
            'path' => $path,
        ]));
    }
}
