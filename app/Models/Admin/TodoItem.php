<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TodoItem extends BaseModel
{
    use HasFactory;

    protected $table = 'todo_items';

    protected $fillable = [
        'title',
        'content',
        'status',
        'priority',
        'due_date',
        'tags',
        'platform_id'
    ];

    protected $requestFilters = [
        'status' => ['column' => 'status', 'filterType' => 'exact'],
        'priority' => ['column' => 'priority', 'filterType' => 'exact'],
        'platform_id' => ['column' => 'platform_id', 'filterType' => 'exact'],
    ];

    public function platform()
    {
        return $this->belongsTo(WorkPlatform::class, 'platform_id');
    }

    public function getTagsAttribute($value)
    {
        if (!$value) {
            return [];
        }
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }
        if (is_string($value)) {
            return array_values(array_filter(array_map('trim', explode(',', $value))));
        }
        return [];
    }

    public function setTagsAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['tags'] = json_encode($value, JSON_UNESCAPED_UNICODE);
            return;
        }
        $this->attributes['tags'] = $value;
    }
}
