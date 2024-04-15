<?php

namespace App\Http\Controllers\Api\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\FormRequest;
use App\Http\Resources\Web\WeiYanResource;
use App\Models\Web\Article;
use App\Models\Web\WeiYan;
use Doctrine\DBAL\Query;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class WeiYanController extends Controller
{
    public function index(FormRequest $request, WeiYan $weiYan)
    {
        $weiYanList = QueryBuilder::for($weiYan)->where([
            'source' => $request->get('source'),
            'type' => $request->get('type')
        ])->get();

        return WeiYanResource::collection($weiYanList);
    }
}
