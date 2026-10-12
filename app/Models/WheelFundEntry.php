<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WheelFundEntry extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'reference', 'delta_cents', 'balance_after_cents'];

    protected $casts = ['delta_cents' => 'integer', 'balance_after_cents' => 'integer'];
}
