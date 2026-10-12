<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KycSubmission extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'document_path', 'selfie_path', 'status', 'version', 'reason', 'reviewer_id', 'reviewed_at'];

    protected $casts = ['document_path' => 'encrypted', 'selfie_path' => 'encrypted', 'reviewed_at' => 'datetime', 'version' => 'integer'];

    protected $hidden = ['document_path', 'selfie_path'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
