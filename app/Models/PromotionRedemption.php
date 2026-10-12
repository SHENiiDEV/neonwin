<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionRedemption extends Model
{
    use HasFactory;

    protected $fillable = ['promotion_id', 'user_id', 'deposit_id', 'request_id', 'status', 'bonus_cents', 'snapshot', 'message'];

    protected $casts = ['snapshot' => 'array', 'bonus_cents' => 'integer'];

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
