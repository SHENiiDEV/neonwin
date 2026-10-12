<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NexusOperation extends Model
{
    use HasFactory;

    protected $fillable = ['external_id', 'actor_id', 'user_id', 'method', 'provider_code', 'game_code', 'status', 'request', 'result', 'progress', 'active_tour_key', 'expires_at'];

    protected $casts = ['request' => 'array', 'result' => 'array', 'progress' => 'array', 'expires_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
