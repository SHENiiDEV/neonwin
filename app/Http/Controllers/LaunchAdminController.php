<?php

namespace App\Http\Controllers;

use App\Jobs\SendRegistrationNotice;
use App\Models\AdminAudit;
use App\Models\LaunchSetting;
use App\Models\RegistrationNotice;
use App\Models\WheelFundEntry;
use App\Services\EuroMoney;
use App\Services\WheelService;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LaunchAdminController extends Controller
{
    public function index(): Response
    {
        $settings = LaunchSetting::findOrFail(1);

        return Inertia::render('AdminLaunch', [
            'settings' => $settings,
            'telegram' => ['configured' => (bool) $settings->telegram_token, 'group_id' => $settings->telegram_group_id],
            'fundEntries' => WheelFundEntry::latest('id')->limit(20)->get(),
            'notices' => RegistrationNotice::with('user:id,name,surname,email,contact_channel,contact_handle,contact_consent_at')->latest('id')->paginate(20)->through(fn ($notice) => [
                'id' => $notice->id, 'user_id' => $notice->user_id, 'name' => $notice->user->name.' '.$notice->user->surname, 'email' => $notice->user->email,
                'channel' => $notice->user->contact_channel, 'contact' => $notice->user->contact_handle, 'consent' => (bool) $notice->user->contact_consent_at,
                'status' => $notice->status, 'error' => $notice->error, 'sent_at' => $notice->sent_at, 'created_at' => $notice->created_at,
            ]),
        ]);
    }

    public function wheel(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'wheel_enabled' => ['required', 'boolean'], 'wheel_ends_at' => ['nullable', 'date'],
            'wheel_prizes' => ['required', 'array', 'size:8', 'list'], 'wheel_prizes.*' => ['required', 'array:cents,weight'],
            'wheel_prizes.*.cents' => ['required', 'integer', 'min:1', 'max:100000'], 'wheel_prizes.*.weight' => ['required', 'integer', 'min:1', 'max:10000'],
        ]);
        $data['wheel_prizes'] = array_map(fn (array $prize): array => ['cents' => (int) $prize['cents'], 'weight' => (int) $prize['weight']], $data['wheel_prizes']);
        $data['wheel_ends_at'] = empty($data['wheel_ends_at']) ? null : CarbonImmutable::parse($data['wheel_ends_at'], 'UTC');
        $this->save($request, $data, 'wheel_settings_saved');

        return back()->with('success', 'Wheel prizes and availability saved.');
    }

    public function fund(Request $request, WheelService $service): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'regex:/^(0|[1-9][0-9]{0,6})(\.[0-9]{1,2})?$/D'], 'request_id' => ['required', 'uuid']]);
        $service->fund($request->user(), EuroMoney::cents($data['amount']), $data['request_id']);

        return back()->with('success', 'Your EUR wallet funded the daily wheel.');
    }

    public function rakeback(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rakeback_enabled' => ['required', 'boolean'], 'rakeback_basis' => ['required', Rule::in(['stakes', 'net_loss'])],
            'percent' => ['required', 'numeric', 'between:0,100', 'decimal:0,2'],
            'starts_on' => ['nullable', 'required_if:rakeback_enabled,1', 'date_format:Y-m-d', Rule::when($request->boolean('rakeback_enabled'), 'after:today')],
            'ends_on' => ['nullable', 'required_if:rakeback_enabled,1', 'date_format:Y-m-d', 'after_or_equal:starts_on'],
        ]);
        $this->save($request, [
            'rakeback_enabled' => $data['rakeback_enabled'], 'rakeback_basis' => $data['rakeback_basis'], 'rakeback_bps' => EuroMoney::cents((string) $data['percent']),
            'rakeback_starts_at' => empty($data['starts_on']) ? null : CarbonImmutable::parse($data['starts_on'], 'UTC')->startOfDay(),
            'rakeback_ends_at' => empty($data['ends_on']) ? null : CarbonImmutable::parse($data['ends_on'], 'UTC')->startOfDay()->addDay(),
        ], 'launch_rakeback_saved');

        return back()->with('success', 'Launch rakeback saved. Scheduled changes begin at midnight UTC.');
    }

    public function telegram(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'telegram_enabled' => ['required', 'boolean'], 'telegram_token' => ['nullable', 'string', 'max:200', 'regex:/^[0-9]+:[A-Za-z0-9_-]{20,}$/D'],
            'telegram_group_id' => ['nullable', 'string', 'regex:/^-[0-9]{1,20}$/D'],
        ]);
        $settings = LaunchSetting::findOrFail(1);
        if ($data['telegram_enabled'] && (! ($data['telegram_token'] ?? $settings->telegram_token) || empty($data['telegram_group_id']))) {
            throw ValidationException::withMessages(['telegram_group_id' => 'Enabling notifications requires a bot token and numeric group ID.']);
        }
        if (empty($data['telegram_token'])) {
            unset($data['telegram_token']);
        }
        $this->save($request, $data, 'registration_telegram_saved', ['telegram_token', 'telegram_group_id']);

        return back()->with('success', 'Telegram registration notifications saved.');
    }

    public function retry(Request $request, RegistrationNotice $notice): RedirectResponse
    {
        $request->validate(['confirmed' => ['accepted']]);
        $settings = LaunchSetting::findOrFail(1);
        if (! $settings->telegram_enabled || ! $settings->telegram_token || ! $settings->telegram_group_id) {
            throw ValidationException::withMessages(['telegram' => 'Configure and enable Telegram first.']);
        }
        DB::transaction(function () use ($request, $notice): void {
            $notice = RegistrationNotice::lockForUpdate()->findOrFail($notice->id);
            if ($notice->status === 'sent') {
                return;
            }
            if ($notice->status === 'sending' && $notice->attempted_at?->gt(now()->subMinutes(2))) {
                throw ValidationException::withMessages(['telegram' => 'Delivery is already in progress.']);
            }
            $notice->update(['status' => 'pending', 'error' => null]);
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'registration_notice_retry', 'target' => (string) $notice->id]);
            SendRegistrationNotice::dispatch($notice->id)->afterCommit();
        }, 3);

        return back()->with('success', 'Registration notification queued.');
    }

    private function save(Request $request, array $data, string $action, array $private = []): void
    {
        DB::transaction(function () use ($request, $data, $action, $private): void {
            $settings = LaunchSetting::lockForUpdate()->findOrFail(1);
            $before = $settings->only(array_diff(array_keys($data), $private));
            $settings->fill($data)->save();
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => $action, 'target' => 'launch_settings:1', 'before' => $before, 'after' => $settings->only(array_diff(array_keys($data), $private))]);
        }, 3);
    }
}
