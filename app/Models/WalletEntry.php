<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletEntry extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'reference', 'kind', 'delta_cents', 'balance_before_cents', 'balance_after_cents', 'currency'];

    protected $casts = ['delta_cents' => 'integer', 'balance_before_cents' => 'integer', 'balance_after_cents' => 'integer'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
