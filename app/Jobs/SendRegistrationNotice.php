<?php

namespace App\Jobs;

use App\Models\LaunchSetting;
use App\Models\RegistrationNotice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Throwable;

class SendRegistrationNotice implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $noticeId) {}

    public function handle(): void
    {
        $settings = LaunchSetting::findOrFail(1);
        if (! $settings->telegram_enabled || ! $settings->telegram_token || ! $settings->telegram_group_id) {
            return;
        }
        $notice = DB::transaction(function (): ?RegistrationNotice {
            $notice = RegistrationNotice::lockForUpdate()->find($this->noticeId);
            if (! $notice || $notice->status !== 'pending') {
                return null;
            }
            $notice->update(['status' => 'sending', 'attempted_at' => now(), 'error' => null]);

            return $notice;
        }, 3);
        if (! $notice) {
            return;
        }
        $user = $notice->user;
        $contact = $user->contact_handle ? $user->contact_channel.': '.$user->contact_handle : 'Not provided';
        $text = "NEONWIN · New registration #{$user->id}\nName: {$user->name} {$user->surname}\nEmail: {$user->email}\nContact: {$contact}\nWelcome contact consent: ".($user->contact_consent_at ? 'Yes' : 'No — do not initiate promotional contact')."\nRegistered: {$user->created_at->toIso8601String()}";
        try {
            $response = Http::connectTimeout(5)->timeout(10)->withOptions(['allow_redirects' => false])->post('https://api.telegram.org/bot'.$settings->telegram_token.'/sendMessage', [
                'chat_id' => $settings->telegram_group_id, 'text' => $text, 'protect_content' => true,
            ]);
            if ($response->successful() && $response->json('ok') === true) {
                $notice->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);
            } else {
                $notice->update(['status' => 'failed', 'error' => 'Telegram rejected delivery. Check bot permissions, token and group ID.']);
            }
        } catch (Throwable) {
            $notice->update(['status' => 'uncertain', 'error' => 'Delivery could not be confirmed. Check the group before retrying to avoid duplicates.']);
        }
    }
}
