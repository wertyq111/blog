<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\ProductImageExtractorRequest;
use App\Services\Api\Admin\ProductImage\ProductImageExtractorService;
use App\Services\Api\Admin\ProductImage\ProductImageZipService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductImageExtractorController extends Controller
{
    /**
     * 初始化商品图片提取控制器。
     *
     * @param ProductImageExtractorService $extractorService
     * @param ProductImageZipService $zipService
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function __construct(
        private readonly ProductImageExtractorService $extractorService,
        private readonly ProductImageZipService $zipService,
    ) {
        parent::__construct();
    }

    /**
     * 获取支持的商品平台。
     *
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function platforms(): JsonResponse
    {
        return response()->json($this->extractorService->platforms());
    }

    /**
     * 提取商品图片及元数据。
     *
     * @param ProductImageExtractorRequest $request
     * @return JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function extract(ProductImageExtractorRequest $request): JsonResponse
    {
        return response()->json($this->extractorService->extract(
            $request->validated('platform'),
            $request->validated('url'),
        ));
    }

    /**
     * 下载选中的商品图片压缩包。
     *
     * @param ProductImageExtractorRequest $request
     * @return BinaryFileResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2026/7/20
     */
    public function download(ProductImageExtractorRequest $request): BinaryFileResponse
    {
        $selection = $this->extractorService->selectForDownload(
            $request->validated('platform'),
            $request->validated('url'),
            $request->validated('image_ids'),
        );
        $archive = $this->zipService->create(
            $selection['adapter'],
            $selection['product'],
            $selection['images'],
        );

        return response()
            ->download($archive['path'], $archive['name'], ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend(true);
    }
}
