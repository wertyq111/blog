<?php

namespace App\Services\Api\MiniProgram;

use App\Models\MiniProgram\House;
use App\Services\Api\BaseService;

class ImageService  extends BaseService
{
    /**
     * 生成图片路径
     *
     * @param $url
     * @param $type
     * @return false|string|null
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 16:10
     */
    public function generateImagePath($url, $type, $data)
    {
        $imagePath = null;

        $paths = config('qiniuimage')['paths'];

        if($paths[$type]) {
            // 获取图片地址/最后一段值
            $urlArray = explode('/', $url);
            $imageName = is_array($urlArray) ? end($urlArray) : null;
            if($paths[$type]['params']) {
                $replaceArray = [];
                // 获取替换值
                foreach($paths[$type]['params'] as $param) {
                    $getParam = "get". ucfirst($param);

                    // 判断方法是否存在
                    $replaceArray[$param] = method_exists($this, $getParam) ? $this->$getParam() : $data[$param];
                }

                if($imageName) {
                    $imageDir = $paths[$type]['dir'];
                    // 对路径进行替换
                    foreach($replaceArray as $key => $value) {
                        $imageDir = str_replace("%{$key}%", $value, $imageDir);
                        $imagePath = $imageDir. $imageName;
                    }
                }
            } else {
                $imagePath = $paths[$type]['dir']. $imageName;
            }
        }

        return $imagePath;
    }

    /**
     * 获取会员 ID
     *
     * @return mixed
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/6/19 15:57
     */
    protected function getMember()
    {
        return auth('api')->user()->member->id;
    }

    protected function getParams()
    {

    }
}
