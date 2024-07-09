<?php

namespace App\Services\Api\MiniProgram;

use App\Services\Api\BaseService;
use App\Services\Api\QiniuService;

class ArticleService extends BaseService
{
    /**
     * 处理文章
     *
     * @param $article
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/9 16:34
     */
    public function handleArticle($article)
    {
        // 处理内容中的图片转换成私有图片地址
        preg_match_all("/<img src=\"(.*)\"/", $article->content, $imageUrls);
        if (is_array($imageUrls) && isset($imageUrls[1])) {
            foreach ($imageUrls[1] as $imageUrl) {
                $privateImageUrl = $this->generatePrivateImageUrl($imageUrl);
                $article->content = str_replace($imageUrl, $privateImageUrl, $article->content);
            }
        }

        // 封面图片转换为私有图片地址
        $article->cover = $this->generatePrivateImageUrl($article->cover);
    }

    /**
     * 生成私有图片地址
     *
     * @param $url
     * @return string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/7/9 16:33
     */
    protected function generatePrivateImageUrl($url)
    {
        $qiniuService = new QiniuService();

        // 图片地址新增略缩参数
        $url = preg_match("/\?imageMogr2\/thumbnail\/!(.*)p/", $url) ? $url : $url. "?imageMogr2/thumbnail/!30p";

        return $qiniuService->getPrivateUrl($url);
    }
}
