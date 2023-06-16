<?php

namespace App\Observers\Web;

use App\Models\Web\Article;
use Illuminate\Support\Facades\DB;

class ArticleObserver
{
    /**
     * 删除对应的回复
     *
     * @param Article $comment
     * @return void
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2023/6/15 16:26
     */
    public function deleted(Article $article)
    {
        // 查询回复
        $comments = DB::table('comment')->where([
            'source' => $article->id,
            'type' => 'article',
            'deleted_at' => 0
        ])->get();

        foreach($comments as $comment) {
            $comment->delete();
        }
    }
}
