<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PomoTask extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'title',
        'estimated_pomos',
        'completed_pomos',
        'done',
        'sort',
    ];

    protected $requestFilters = [
        'done' => ['column' => 'done'],
    ];
}
