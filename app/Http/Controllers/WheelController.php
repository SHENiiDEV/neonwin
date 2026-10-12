<?php

namespace App\Http\Controllers;

use App\Models\LaunchSetting;
use App\Models\WheelSpin;
use App\Services\WheelService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WheelController extends Controller
{
    public function index(Request $request): Response
    {
        $settings = LaunchSetting::findOrFail(1);
        $last = $request->user() ? WheelSpin::where('user_id', $request->user()->id)->latest('id')->first() : null;

        return Inertia::render('DailyWheel', [
            'prizes' => $settings->wheel_prizes,
            'available' => $settings->wheel_enabled && ! $settings->wheel_ends_at?->isPast() && $settings->wheel_budget_cents >= max(array_column($settings->wheel_prizes, 'cents')),
            'endsAt' => $settings->wheel_ends_at?->toIso8601String(),
            'nextSpinAt' => $last?->created_at->addHours(24)->toIso8601String(),
            'history' => $request->user() ? WheelSpin::where('user_id', $request->user()->id)->latest('id')->limit(10)->get(['id', 'amount_cents', 'created_at']) : [],
            'result' => $request->session()->get('wheel_result'),
        ]);
    }

    public function spin(Request $request, WheelService $service): RedirectResponse
    {
        $data = $request->validate(['request_id' => ['required', 'uuid']]);
        $spin = $service->spin($request->user(), $data['request_id']);

        return redirect()->route('wheel')->with('wheel_result', $spin->only(['id', 'prize_index', 'amount_cents', 'prizes']));
    }
}
