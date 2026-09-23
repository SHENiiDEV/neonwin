<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tournament extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'prize_pool',
        'freespins',
        'starts_at',
        'ends_at',
        'status',
        'image_url',
    ];

    protected $casts = [
        'prize_pool' => 'decimal:2',
        'freespins' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];
}
