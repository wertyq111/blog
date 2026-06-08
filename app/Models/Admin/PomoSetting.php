<?php

namespace App\Models\Admin;

use App\Models\BaseModel;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PomoSetting extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'focus_min',
        'short_break_min',
        'long_break_min',
        'long_break_every',
        'auto_start_next',
        'sound_on',
        'white_noise',
        'white_noise_volume',
    ];

    protected $casts = [
        'white_noise_volume' => 'float',
    ];

    protected $attributes = [
        'focus_min' => 25,
        'short_break_min' => 5,
        'long_break_min' => 15,
        'long_break_every' => 4,
        'auto_start_next' => 0,
        'sound_on' => 1,
        'white_noise' => null,
        'white_noise_volume' => 0.60,
    ];
}
