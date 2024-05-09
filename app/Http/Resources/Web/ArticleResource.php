<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\BaseResource;
use App\Http\Resources\User\MemberResource;
use Illuminate\Http\Request;

class ArticleResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content' => $this->content,
            'cover' => $this->cover,
            'member' => new MemberResource($this->whenLoaded('member')),
            'viewStatus' => $this->view_status ? true : false,
            'viewCount' => $this->view_count,
            'likeCount' => $this->like_count,
            'recommendStatus' => $this->recommend_status ? true : false,
            'commentCount' => 0,
            'commentStatus' => $this->comment_status ? true : false,
            'categoryId' => $this->category_id,
            'labelId' => $this->label_id,
            'createTime' => (string)$this->created_at,
            'updateTime' => (string)$this->updated_at,
            'category' => new CategoryResource($this->whenLoaded('category')),
            'label' => new LabelResource($this->whenLoaded('label'))
        ];
    }
}
