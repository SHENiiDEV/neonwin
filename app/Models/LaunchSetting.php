<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaunchSetting extends Model
{
    use HasFactory;

    protected $guarded = ['id', 'wheel_budget_cents'];

    protected $hidden = ['telegram_token', 'telegram_group_id'];

    protected $casts = ['wheel_enabled' => 'boolean', 'wheel_budget_cents' => 'integer', 'wheel_prizes' => 'array', 'wheel_ends_at' => 'immutable_datetime', 'rakeback_enabled' => 'boolean', 'rakeback_bps' => 'integer', 'rakeback_starts_at' => 'immutable_datetime', 'rakeback_ends_at' => 'immutable_datetime', 'telegram_enabled' => 'boolean', 'telegram_token' => 'encrypted', 'telegram_group_id' => 'encrypted'];
}
