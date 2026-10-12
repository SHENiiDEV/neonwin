<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OperationalAlert extends Model
{
    use HasFactory;

    protected $fillable = ['kind', 'reference', 'message', 'status', 'resolved_by', 'resolved_at'];

    protected $casts = ['resolved_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
