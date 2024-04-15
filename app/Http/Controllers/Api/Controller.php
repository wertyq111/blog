<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller as BaseController;
use App\Services\Api\QiniuService;

class Controller extends BaseController
{
    public function __construct()
    {
        $this->qiniuService = new QiniuService();
    }
}
