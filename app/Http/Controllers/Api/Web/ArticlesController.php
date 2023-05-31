<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Web\ArticleResource;
use App\Models\Web\Article;

class ArticlesController extends Controller
{
    public function index(Article $article)
    {
        return new ArticleResource($article::paginate());
    }
}
