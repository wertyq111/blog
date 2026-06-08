<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PomoSession extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'day',
        'completed_at',
    ];
}
