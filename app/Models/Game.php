<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_code',
        'game_code',
        'name',
        'slug',
        'banner_url',
        'category',
        'rtp',
        'is_popular',
        'is_featured',
        'status',
    ];

    protected $casts = [
        'rtp' => 'decimal:2',
        'is_popular' => 'boolean',
        'is_featured' => 'boolean',
        'status' => 'boolean',
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
