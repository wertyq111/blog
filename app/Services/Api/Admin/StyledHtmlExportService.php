<?php

namespace App\Services\Api\Admin;

use Illuminate\Support\Str;

class StyledHtmlExportService
{
    /**
     * 把 Markdown 渲染为带样式的完整 HTML 文档。
     *
     * @param string $title 文档标题
     * @param string $markdown Markdown 源
     * @return string 完整 HTML 字符串
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/6/29
     */
    public function render(string $title, string $markdown): string
    {
        $body = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return view('exports.styled-document', ['title' => $title, 'body' => $body])->render();
    }
}
