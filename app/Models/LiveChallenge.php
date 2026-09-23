<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveChallenge extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'game_code',
        'game_name',
        'target_multiplier',
        'min_bet',
        'prize_sc',
        'winner_user_id',
        'winner_name',
        'winner_multiplier',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'target_multiplier' => 'decimal:2',
        'min_bet' => 'decimal:2',
        'prize_sc' => 'decimal:2',
        'winner_multiplier' => 'decimal:2',
        'expires_at' => 'datetime',
    ];

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_user_id');
    }
}
