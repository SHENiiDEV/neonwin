<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LiveCommunityWin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'user_avatar',
        'game_name',
        'game_image',
        'bet_amount',
        'multiplier',
        'win_amount',
    ];

    protected $casts = [
        'bet_amount' => 'decimal:2',
        'multiplier' => 'decimal:2',
        'win_amount' => 'decimal:2',
    ];
}
