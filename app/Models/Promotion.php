<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'kind', 'enabled', 'percent_bps', 'min_deposit_cents', 'max_bonus_cents', 'redemption_limit', 'starts_at', 'ends_at', 'reward', 'terms', 'created_by'];

    protected $casts = ['enabled' => 'boolean', 'percent_bps' => 'integer', 'min_deposit_cents' => 'integer', 'max_bonus_cents' => 'integer', 'redemption_limit' => 'integer', 'starts_at' => 'datetime', 'ends_at' => 'datetime', 'reward' => 'array'];

    public function redemptions(): HasMany
    {
        return $this->hasMany(PromotionRedemption::class);
    }

    public function available(): bool
    {
        return $this->enabled && (! $this->starts_at || $this->starts_at->isPast()) && (! $this->ends_at || $this->ends_at->isFuture());
    }

    public function snapshot(): array
    {
        return $this->only(['name', 'code', 'kind', 'percent_bps', 'min_deposit_cents', 'max_bonus_cents', 'reward', 'terms']);
    }
}
