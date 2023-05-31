<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Api\Controller;
use App\Http\Resources\Web\WebInfoResource;
use App\Models\Web\WebInfo;

class InfoController extends Controller
{
    public function index(WebInfo $info)
    {
        return new WebInfoResource($info->find(1));
    }
}
