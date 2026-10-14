<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration', 'external_code', 'has_freespins',
        'provider_code',
        'game_code',
        'name',
        'slug',
        'banner_url',
        'category',
        'rtp',
        'is_popular',
        'is_featured',
        'status', 'bet_levels',
        'mobile_supported',
    ];

    protected $casts = [
        'has_freespins' => 'boolean',
        'rtp' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_featured' => 'boolean',
        'status' => 'boolean',
        'mobile_supported' => 'boolean',
        'bet_levels' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function ($game) {
            if (empty($game->slug)) {
                $game->slug = Str::slug($game->name.'-'.$game->game_code);
            }
        });
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_code', 'code');
    }
}
