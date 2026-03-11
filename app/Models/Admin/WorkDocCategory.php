<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class WorkDocCategory extends BaseModel
{
    use HasFactory;

    protected $table = 'work_doc_categories';

    protected $fillable = [
        'parent_id',
        'name',
        'icon',
        'description',
        'sort',
        'status'
    ];

    protected $requestFilters = [
        'parent_id' => ['column' => 'parent_id', 'filterType' => 'exact'],
        'status' => ['column' => 'status', 'filterType' => 'exact'],
    ];

    public function docs()
    {
        return $this->hasMany(WorkDoc::class, 'category_id');
    }
}
