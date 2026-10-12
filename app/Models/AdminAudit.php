<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminAudit extends Model
{
    use HasFactory;

    protected $fillable = ['actor_id', 'action', 'target', 'before', 'after'];

    protected $casts = ['before' => 'array', 'after' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
