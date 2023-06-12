<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Web\ArticleResource;
use App\Models\Web\Article;

class ArticlesController extends Controller
{
    /**
     * 文章列表
     *
     * @param Article $article
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:12
     */
    public function index(Article $article)
    {
        return ArticleResource::collection($article::paginate());
    }
}
