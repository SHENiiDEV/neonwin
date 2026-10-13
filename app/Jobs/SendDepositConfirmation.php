<?php

namespace App\Jobs;

use App\Mail\DepositConfirmedMail;
use App\Models\Deposit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendDepositConfirmation implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $depositId) {}

    public function handle(): void
    {
        $deposit = Deposit::with('user:id,name,email,account_emails_enabled')->find($this->depositId);
        if (! $deposit || $deposit->status !== 'confirmed' || ! $deposit->user?->account_emails_enabled) {
            return;
        }

        Mail::to($deposit->user->email)->send(new DepositConfirmedMail(
            $deposit->user->name,
            $deposit->amount_cents,
            $deposit->reference,
            $deposit->confirmed_at->copy()->utc()->format('d M Y, H:i'),
        ));
    }
}
