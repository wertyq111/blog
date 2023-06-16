<?php

namespace App\Observers\Web;

use App\Models\Web\Comment;

class CommentObserver
{
    /**
     * 删除对应的子回复
     *
     * @param Comment $comment
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 16:26
     */
    public function deleted(Comment $comment)
    {
        $children = Comment::query()->where('comment_id', '=', $comment->id)->get();
        if($children != null) {
            foreach($children as $child) {
                $child->delete();
            }
        }
    }
}
