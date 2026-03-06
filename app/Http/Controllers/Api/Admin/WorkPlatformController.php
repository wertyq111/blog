<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Models\Admin\WorkPlatform;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class WorkPlatformController extends Controller
{
    /**
     * 列表（分页）
     */
    public function index(FormRequest $request, WorkPlatform $workPlatform)
    {
        $allowedFilters = $request->generateAllowedFilters($workPlatform->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters,
            'orderBy' => [[
                'sort' => 'asc'
            ], [
                'id' => 'desc'
            ]],
            'perPage' => $request->input('limit', 10)
        ];

        $platforms = $this->queryBuilder($workPlatform, true, $config);

        return $this->resource($platforms, ['time' => true, 'collection' => true]);
    }

    // 现有方法

}

