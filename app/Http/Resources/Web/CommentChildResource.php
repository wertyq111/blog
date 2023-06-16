<?php

namespace App\Http\Resources\Web;

use App\Http\Resources\BaseResource;
use App\Models\Web\Comment;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\QueryBuilder;

class CommentChildResource extends BaseResource
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
            'source' => $this->source,
            'type' => $this->type,
            'member' => $this->member,
            'parent' => new CommentChildResource($this->parent),
            'likeCount' => $this->like_count,
            'content' => $this->content,
            'info' => $this->info,
            'createdAt' => (string)$this->created_at,
            'updatedAt' => (string)$this->updated_at
        ];
    }
}
