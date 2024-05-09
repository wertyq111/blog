<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\BaseResource;
use Illuminate\Http\Request;

class CategoryResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'priority' => $this->priority,
            'type' => $this->type,
            'labels' => new LabelResource($this->whenLoaded('labels')),
            'articles' => $this->whenLoaded('articles'),
            'createTime' => (string) $this->created_at,
            'updateTime' => (string) $this->updated_at,
        ];
    }
}
