<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipQuest extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'kind', 'provider_code', 'game_code', 'target', 'min_bet_cents', 'reward_cents', 'enabled'];

    protected $casts = ['target' => 'integer', 'min_bet_cents' => 'integer', 'reward_cents' => 'integer', 'enabled' => 'boolean'];
}
