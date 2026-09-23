<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'user_avatar',
        'vip_level',
        'message',
        'room',
    ];

    protected $casts = [
        'vip_level' => 'integer',
    ];
}
