<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Article\ArticleResource;
use App\Models\Article\Article;

class ArticlesController extends Controller
{
    public function index(Article $article)
    {
        return new ArticleResource($article::paginate());
    }
}
