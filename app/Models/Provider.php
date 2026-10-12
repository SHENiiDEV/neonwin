<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'integration', 'external_code',
        'code',
        'name',
        'logo_url',
        'status', 'type', 'synced_at',
    ];

    protected $casts = [
        'status' => 'boolean',
        'synced_at' => 'datetime',
    ];

    public function games()
    {
        return $this->hasMany(Game::class, 'provider_code', 'code');
    }
}
