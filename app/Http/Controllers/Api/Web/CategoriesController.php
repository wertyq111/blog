<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Article\CategoryResource;
use App\Models\Article\Category;

class CategoriesController extends Controller
{
    public function index(Category $category)
    {
        return new CategoryResource($category->get());
    }
}
