<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'user_code' => $user->user_code,
                    'game_balance' => (float) $user->game_balance,
                    'vip_xp' => (int) $user->vip_xp,
                    'vip_level' => (int) $user->vip_level,
                    'currency' => $user->currency ?? 'SC',
                    'avatar' => $user->avatar,
                    'vault_balance' => (float) $user->vault_balance,
                    'has_vault_pin' => ! empty($user->vault_pin),
                    'referral_code' => $user->referral_code,
                    'sound_enabled' => (bool) $user->sound_enabled,
                    'pending_crates_count' => $user->crates()->where('status', 'pending')->count(),
                    'last_daily_bonus_at' => $user->last_daily_bonus_at?->toIso8601String(),
                    'can_claim_daily_bonus' => $user->canClaimDailyBonus(),
                    'next_daily_bonus_at' => $user->nextDailyBonusAt()?->toIso8601String(),
                ] : null,
            ],
            'company' => [
                'name' => config('company.name', 'Neonwin Interactive Ltd.'),
                'number' => config('company.number', 'HE 448921'),
                'address' => config('company.address', 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus'),
                'email' => config('company.email', 'support@neonwin.com'),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
                'success' => fn () => $request->session()->get('success'),
            ],
            'app_name' => config('app.name', 'NEONWIN'),
        ]);
    }
}
