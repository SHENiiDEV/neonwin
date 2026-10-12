<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSession extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'provider_code', 'game_code', 'token', 'token_hash', 'fc_code', 'status', 'last_activity_at'];

    protected $casts = ['token' => 'encrypted', 'last_activity_at' => 'datetime'];

    protected $hidden = ['token', 'token_hash', 'fc_code'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
