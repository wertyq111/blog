<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkDoc extends BaseModel
{
    use HasFactory;

    protected $table = 'work_docs';

    protected $fillable = [
        'category_id',
        'title',
        'content',
        'template_type',
        'tags',
        'status',
        'priority',
        'source',
        'is_pin'
    ];

    protected $requestFilters = [
        'category_id' => ['column' => 'category_id', 'filterType' => 'exact'],
        'status' => ['column' => 'status', 'filterType' => 'exact'],
        'template_type' => ['column' => 'template_type', 'filterType' => 'exact'],
        'is_pin' => ['column' => 'is_pin', 'filterType' => 'exact'],
        'priority' => ['column' => 'priority', 'filterType' => 'exact'],
    ];

    public function category()
    {
        return $this->belongsTo(WorkDocCategory::class, 'category_id');
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
