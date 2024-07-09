<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\Web\ArticleRequest;
use App\Http\Resources\Web\ArticleResource;
use App\Models\Web\Article;
use App\Services\Api\MiniProgram\ArticleService;
use Illuminate\Http\Request;

class ArticlesController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->service = new ArticleService();
    }

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
        $data = $request->all();

        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($article->getRequestFilters());

        $config = [
            'includes' => ['member', 'category', 'label'],
            'allowedFilters' => $allowedFilters
        ];
        $articles = $this->queryBuilder($article, true, $config);

        foreach($articles as $article) {
            $this->service->handleArticle($article);
        }

        return ArticleResource::collection($articles);
    }

    /**
     * 文章列表(前台)
     *
     * @param FormRequest $request
     * @param Article $article
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/14 14:57
     */
    public function list(FormRequest $request, Article $article)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($article->getRequestFilters());

        $config = [
            'includes' => ['member', 'category', 'label'],
            'allowedFilters' => $allowedFilters
        ];
        $articles = $this->queryBuilder($article, true, $config);

        foreach($articles as $article) {
            $this->service->handleArticle($article);
        }

        return ArticleResource::collection($articles);
    }

    /**
     * 文章详情(前台)
     *
     * @param Article $article
     * @return ArticleResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/8 15:35
     */
    public function show(Article $article)
    {
        // 前台访问文章时增加阅读数量
        $article->view_count += 1;
        $article->edit(false);
        $article->author = $article->member->nickname;
        $article->goods;
        $this->service->handleArticle($article);

        return $this->resource($article, ['time' => true]);
    }

    /**
     * 文章详情(后台)
     *
     * @param Article $article
     * @return ArticleResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/13 09:15
     */
    public function info(Article $article)
    {
        $this->service->handleArticle($article);
        return new ArticleResource($article);
    }

    /**
     * 点赞
     *
     * @param Article $article
     * @return mixed|string
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/14 15:56
     */
    public function good(Article $article)
    {
        // 添加点赞记录
        $member = auth()->user()->member;
        $article->goods()->sync($member->id, false);

        $article->like_count = count($article->goods);
        $article->edit();

        return $this->resource($article);
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

        // 将内容中的图片地址进行转换
        $data['content'] = preg_replace("/<img (.+)\?(.*)\/>/", "<img $1\"/>", $data['content']);

        // 将背景的图片地址进行转换
        $data['cover'] = preg_replace("/(.+)\?(.*)/", "$1", $data['cover']);

        $article->fill($data);
        $article->member_id = $request->user()->member ? $request->user()->member->id : 0;

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
        $data = $request->getSnakeRequest();

        // 将内容中的图片地址进行转换
        $data['content'] = preg_replace("/<img (.+)\?(.*)\/>/", "<img $1\"/>", $data['content']);

        // 将背景的图片地址进行转换
        $data['cover'] = preg_replace("/(.+)\?(.*)/", "$1", $data['cover']);

        $article->fill($data);

        $article->edit();

        return new ArticleResource($article);
    }

    /**
     * 删除文章
     *
     * @param Article $article
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/5/8 15:36
     */
    public function delete(Article $article)
    {
        $article->delete();

        return response()->json([]);
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
