<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use RuntimeException;

class ProductImageExtractorException extends RuntimeException
{
    /**
     * 初始化商品图片提取异常。
     *
     * @param string $message
     * @param int $status
     * @param int|null $remoteStatus
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(
        string $message,
        private readonly int $status = 422,
        private readonly ?int $remoteStatus = null,
    )
    {
        parent::__construct($message);
    }

    /**
     * 获取远程资源响应状态。
     *
     * @return int|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function remoteStatus(): ?int
    {
        return $this->remoteStatus;
    }

    /**
     * 渲染商品图片提取异常响应。
     *
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function render(): JsonResponse
    {
        return response()->json(['message' => $this->getMessage()], $this->status);
    }
}
