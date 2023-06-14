<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ResourceRequest;
use App\Http\Resources\ResourceResource;
use App\Models\Resource;

class ResourceController extends Controller
{
    public function edit(ResourceRequest $request, Resource $resource)
    {
        $resource->fill($request->getSnakeRequest());
        $resource->member_id = $request->user()->member->id;
        $resource->edit();
        return new ResourceResource($resource);
    }
}
