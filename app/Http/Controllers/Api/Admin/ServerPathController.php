<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Controller;
use App\Http\Requests\Api\Admin\MenuRequest;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\BaseResource;
use App\Models\Admin\ServerPath;
use App\Services\Api\Admin\ServerPathService;
use GuzzleHttp\Utils;

class ServerPathController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->service = new ServerPathService();
    }


    /**
     * 服务器路径列表 - 分页
     *
     * @param FormRequest $request
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 09:52
     */
    public function index(FormRequest $request, ServerPath $serverPath)
    {
        // 生成允许过滤字段数组
        $allowedFilters = $request->generateAllowedFilters($serverPath->getRequestFilters());

        $config = [
            'allowedFilters' => $allowedFilters
        ];
        $serverPaths = $this->queryBuilder($serverPath, true, $config);

        return $this->resource($serverPaths, ['time' => true, 'collection' => true]);
    }

    /**
     * 服务器路径详情
     *
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/18 13:28
     */
    public function info(ServerPath $serverPath)
    {
        return $this->resource($serverPath);
    }

    /**
     * 添加服务器路径
     *
     * @param MenuRequest $request
     * @param ServerPath $serverPath
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:07
     */
    public function add(FormRequest $request, ServerPath $serverPath)
    {
        $data = $request->getSnakeRequest();

        if (isset($data['sources']) && is_array($data['sources'])) {
            $data['sources'] = Utils::jsonEncode($data['sources']);
        }

        $serverPath->fill($data);

        $serverPath->edit();

        return $this->resource($serverPath);
    }

    /**
     * 编辑服务器路径
     *
     * @param ServerPath $serverPath
     * @param FormRequest $request
     * @return BaseResource
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:08
     */
    public function edit(ServerPath $serverPath, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        if (isset($data['sources']) && is_array($data['sources'])) {
            $data['sources'] = Utils::jsonEncode($data['sources']);
        }

        $serverPath->fill($data);

        $serverPath->edit();

        return $this->resource($serverPath);
    }

    /**
     * 服务器路径转换
     *
     * @param ServerPath $serverPath
     * @param FormRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/4/28 13:24
     */
    public function convert(ServerPath $serverPath, FormRequest $request)
    {
        $data = $request->getSnakeRequest();

        $serverPaths = $this->service->convert($serverPath, $data['paths']);

        return response()->json($serverPaths);
    }

    /**
     * 删除服务器路径
     *
     * @param ServerPath $serverPath
     * @return \Illuminate\Http\JsonResponse
     * @author zhouxufeng <zxf@netsun.com>
     * @date 2024/3/11 13:23
     */
    public function delete(ServerPath $serverPath)
    {
        $serverPath->delete();

        return response()->json([]);
    }
}
