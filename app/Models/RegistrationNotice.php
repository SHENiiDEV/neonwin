<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegistrationNotice extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'status', 'error', 'sent_at', 'attempted_at'];

    protected $casts = ['sent_at' => 'immutable_datetime', 'attempted_at' => 'immutable_datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
