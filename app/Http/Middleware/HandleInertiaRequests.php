<?php

namespace App\Http\Middleware;

use App\Models\Promotion;
use App\Models\VipAccount;
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
        $welcomeOffer = Promotion::where('kind', 'first_deposit')->first();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'is_admin' => (bool) $user->is_admin,
                    'kyc_approved' => $user->hasApprovedKyc(),
                    'name' => $user->name,
                    'surname' => $user->surname, 'phone' => $user->phone, 'street' => $user->street, 'city' => $user->city, 'postcode' => $user->postcode,
                    'email' => $user->email,
                    'user_code' => $user->user_code,
                    'game_balance' => $user->eur_balance_cents / 100,
                    'vip_xp' => (int) $user->vip_xp,
                    'vip_level' => (int) (VipAccount::where('user_id', $user->id)->value('level') ?? 1),
                    'currency' => 'EUR',
                    'avatar' => $user->avatar,
                    'vault_balance' => $user->eur_vault_cents / 100,
                    'has_vault_pin' => ! empty($user->vault_pin),
                    'referral_code' => $user->referral_code,
                    'sound_enabled' => (bool) $user->sound_enabled,
                    'pending_crates_count' => $user->crates()->where('status', 'pending')->count(),
                    'last_daily_bonus_at' => $user->last_daily_bonus_at?->toIso8601String(),
                    'can_claim_daily_bonus' => false,
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
            'welcomeOffer' => $welcomeOffer?->available() ? $welcomeOffer->only(['percent_bps', 'min_deposit_cents', 'max_bonus_cents', 'terms']) : null,
        ]);
    }
}
