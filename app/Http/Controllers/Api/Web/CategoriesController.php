<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Web\CategoryResource;
use App\Models\Web\Category;

class CategoriesController extends Controller
{
    public function index(Category $category)
    {
        return new CategoryResource($category->get());
    }
}
