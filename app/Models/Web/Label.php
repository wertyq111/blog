<?php

namespace App\Models\Web;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Label extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name', 'description'
    ];
}
