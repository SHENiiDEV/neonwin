<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'logo_url',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function games()
    {
        return $this->hasMany(Game::class, 'provider_code', 'code');
    }
}
