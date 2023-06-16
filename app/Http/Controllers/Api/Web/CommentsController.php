<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Requests\Api\Web\CommentRequest;
use App\Http\Resources\Web\CommentResource;
use App\Models\Web\Comment;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @author zhouxufeng <zxf@netsun.com>
 * @date 2023/6/15
 * Class CommentsController
 * @package App\Http\Controllers\Api\Web
 */
class CommentsController extends Controller
{
    /**
     * 评论列表
     *
     * @param FormRequest $request
     * @param Comment $comment
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:22
     */
    public function index(FormRequest $request, Comment $comment)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($comment->getRequestFilters());

        $comments = QueryBuilder::for($comment)
            ->allowedIncludes('member', 'parent', 'children')
            ->allowedFilters($allowedFilters)
            ->paginate($request->size);

        return CommentResource::collection($comments);
    }

    /**
     * 评论列表(不分页)
     *
     * @param FormRequest $request
     * @param Comment $comment
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:24
     */
    public function list(FormRequest $request, Comment $comment)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($comment->getRequestFilters());

        $comments = QueryBuilder::for($comment)
            ->allowedIncludes('member', 'parent', 'children')
            ->allowedFilters($allowedFilters)
            ->get();


        return CommentResource::collection($comments);
    }


    /**
     * 评论详情(前台)
     *
     * @param $commentId
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:24
     */
    public function show($commentId)
    {

        $comment = QueryBuilder::for(Comment::class)
            ->allowedIncludes('member', 'parent', 'children')
            ->findOrFail($commentId);

        // 前台访问评论时增加阅读数量
        $comment->view_count += 1;
        $comment->edit(false);

        return (new CommentResource($comment))->response()->setStatusCode(200);
    }

    /**
     * 评论详情(后台)
     *
     * @param $commentId
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:24
     */
    public function detail($commentId)
    {
        $comment = QueryBuilder::for(Comment::class)
            ->allowedIncludes('member')
            ->findOrFail($commentId);
        return (new CommentResource($comment))->response()->setStatusCode(200);
    }

    /**
     * 添加评论
     *
     * @param CommentRequest $request
     * @param Comment $comment
     * @return CommentResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:24
     */
    public function add(CommentRequest $request, Comment $comment)
    {
        $data = $request->getSnakeRequest();

        $comment->fill($data);
        $comment->member_id = $request->user()->member->id;

        $comment->edit();

        return new CommentResource($comment);

    }

    /**
     * 编辑评论
     *
     * @param Comment $comment
     * @param CommentRequest $request
     * @return CommentResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:23
     */
    public function edit(Comment $comment, CommentRequest $request)
    {
        $comment->fill($request->getSnakeRequest());

        $comment->edit();

        return new CommentResource($comment);
    }

    /**
     * 删除评论
     *
     * @param Comment $comment
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Foundation\Application|\Illuminate\Http\Response
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 15:23
     */
    public function delete(Comment $comment)
    {
        $comment->delete();

        return response(null, 204);
    }
}
