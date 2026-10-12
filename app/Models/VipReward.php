<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipReward extends Model
{
    use HasFactory;

    protected $appends = ['claimable_cents'];

    public function getClaimableCentsAttribute(): int
    {
        return max(0, $this->amount_cents - ($this->snapshot['credited_cents'] ?? 0));
    }

    protected $attributes = ['status' => 'active', 'amount_cents' => 0, 'numerator' => 0, 'progress' => 0];

    protected $fillable = ['user_id', 'key', 'kind', 'status', 'amount_cents', 'numerator', 'progress', 'snapshot', 'ends_at', 'claimed_at'];

    protected $casts = ['amount_cents' => 'integer', 'numerator' => 'integer', 'progress' => 'integer', 'snapshot' => 'array', 'ends_at' => 'immutable_datetime', 'claimed_at' => 'immutable_datetime'];
}
