<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Resource extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'type',
        'path',
        'size',
        'mime_type'
    ];
}
