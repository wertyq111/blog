<?php

namespace App\Models\Tobacco;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TobaccoStage extends BaseModel
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name'
    ];
}
