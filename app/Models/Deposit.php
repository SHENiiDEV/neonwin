<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deposit extends Model
{
    use HasFactory;

    protected $fillable = ['reference', 'user_id', 'package', 'amount_cents', 'currency', 'status', 'payment_provider', 'payment_event_id', 'payment_invoice_id', 'payment_test_mode', 'confirmed_at'];

    protected $casts = ['amount_cents' => 'integer', 'payment_test_mode' => 'boolean', 'confirmed_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
