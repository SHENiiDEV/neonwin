<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipBet extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'game_transaction_id', 'amount_cents', 'paid_round_key', 'contributions', 'reversed_at'];

    protected $casts = ['amount_cents' => 'integer', 'contributions' => 'array', 'reversed_at' => 'datetime'];
}
