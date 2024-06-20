<?php

namespace App\Http\Controllers\Api\MiniProgram;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Services\Api\MiniProgram\ImageService;

class ImageController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new ImageService();
    }

    /**
     * 删除
     *
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 15:48
     */
    public function delete(FormRequest $request)
    {
        $url = $request->get('url');
        $type = $request->get('type');
        $data = $request->all();

        // 防盗链访问图片
//        $refer = $configpub['site'];
//        $option = array('http' => array('header' => "Referer: {$refer}"));
//        $context = stream_context_create($option);//创建资源流上下文
//        $file_contents = file_get_contents($url, false, $context);//将整个文件读入一个字符串
//        $thumb_size = getimagesizefromstring($file_contents);//从字符串中获取图像尺寸信息

        $imagePath = $this->service->generateImagePath($url, $type, $data);

        // 删除原先的图片
        if ($imagePath !== null) {
            list($success, $error) = $this->qiniuService->delete($imagePath);
            if ($error) {
                throw new \Exception($error->message());
            }
        }

        return response()->json([]);
    }
}
