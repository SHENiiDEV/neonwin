<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelSpin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'request_id', 'prize_index', 'amount_cents', 'prizes'];

    protected $casts = ['prize_index' => 'integer', 'amount_cents' => 'integer', 'prizes' => 'array', 'created_at' => 'immutable_datetime'];
}
