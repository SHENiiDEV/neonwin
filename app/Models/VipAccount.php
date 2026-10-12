<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipAccount extends Model
{
    use HasFactory;

    protected $attributes = ['level' => 1, 'wager_cents' => 0, 'streak' => 0];

    protected $fillable = ['user_id', 'wager_cents', 'level', 'last_checkin_date', 'streak'];

    protected $casts = ['reward_debt_cents' => 'integer', 'wager_cents' => 'integer', 'level' => 'integer', 'last_checkin_date' => 'date', 'streak' => 'integer'];
}
