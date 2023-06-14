<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\Web\ArticleRequest;
use App\Http\Resources\Web\ArticleResource;
use App\Models\Web\Article;
use Doctrine\DBAL\Query;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class ArticlesController extends Controller
{
    /**
     * 文章列表(后台)
     *
     * @param Request $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 14:29
     */
    public function index(FormRequest $request, Article $article)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($article->getRequestFilters());

        $articles = QueryBuilder::for($article)
            ->allowedIncludes('member', 'category', 'label')
            ->allowedFilters($allowedFilters)
            ->paginate();


        return ArticleResource::collection($articles);
    }

    /**
     * 文章列表(前台)
     *
     * @param Article $article
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 10:12
     */
    public function list(Article $article)
    {
        return ArticleResource::collection($article::paginate());
    }

    /**
     * 文章详情(前台)
     *
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/13 09:15
     */
    public function show($articleId)
    {

        $article = QueryBuilder::for(Article::class)
            ->allowedIncludes('member', 'category', 'label')
            ->findOrFail($articleId);

        // 前台访问文章时增加阅读数量
        $article->view_count += 1;
        $article->edit(false);

        return (new ArticleResource($article))->response()->setStatusCode(200);
    }

    /**
     * 文章详情(后台)
     *
     * @param $articleId
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/13 09:15
     */
    public function detail($articleId)
    {
        $article = QueryBuilder::for(Article::class)
            ->allowedIncludes('member')
            ->findOrFail($articleId);
        return (new ArticleResource($article))->response()->setStatusCode(200);
    }

    /**
     * 添加文章
     *
     * @param ArticleRequest $request
     * @param Article $article
     * @return ArticleResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 15:25
     */
    public function add(ArticleRequest $request, Article $article)
    {
        $data = $request->getSnakeRequest();

        $article->fill($data);
        $article->member_id = $request->user()->member->id;

        $article->edit();

        return new ArticleResource($article);

    }

    /**
     * 编辑文章
     *
     * @param Article $article
     * @param ArticleRequest $request
     * @return ArticleResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 15:25
     */
    public function edit(Article $article, ArticleRequest $request)
    {
        $article->fill($request->getSnakeRequest());

        $article->edit();

        return new ArticleResource($article);
    }

    /**
     * 删除文章
     *
     * @param Article $article
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/12 15:26
     */
    public function delete(Article $article)
    {
        $article->delete();

        return response(null, 204);
    }

    /**
     * 修改文章状态
     *
     * @param Article $article
     * @param FormRequest $request
     * @return ArticleResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/13 14:02
     */
    public function status(Article $article, FormRequest $request)
    {
        $requestData = $request->getSnakeRequest();
        $fixedKeys = ['view_status', 'recommend_status', 'comment_status'];
        foreach($fixedKeys as $key) {
            if(key_exists($key, $requestData)) {
                $article->$key = (boolean)$requestData[$key];
            }
        }
        $article->edit();

        return new ArticleResource($article);
    }
}
