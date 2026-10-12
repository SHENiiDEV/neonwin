<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VipTier extends Model
{
    use HasFactory;

    protected $fillable = ['level', 'name', 'threshold_cents', 'bonus_cents', 'daily_bps', 'weekly_bps', 'monthly_bps', 'checkin_cents'];

    protected $casts = ['level' => 'integer', 'threshold_cents' => 'integer', 'bonus_cents' => 'integer', 'daily_bps' => 'integer', 'weekly_bps' => 'integer', 'monthly_bps' => 'integer', 'checkin_cents' => 'integer'];
}
