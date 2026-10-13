<?php

namespace App\Http\Controllers;

use App\Jobs\SendRegistrationNotice;
use App\Mail\WelcomeMail;
use App\Models\RegistrationNotice;
use App\Models\User;
use App\Models\UserCrate;
use App\Services\PromotionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class AuthController extends Controller
{
    public const RESTRICTED_COUNTRIES = [
        'Sudan',
        'Dem. Rep. of the Congo',
        'Democratic Republic of the Congo',
        'Iran',
        'Mali',
        'Myanmar (Burma)',
        'Myanmar',
        'North Korea',
        'Korea, Democratic People\'s Republic of',
        'South Sudan',
        'Syria',
        'Syrian Arab Republic',
        'Yemen',
        'Afghanistan',
        'Belarus',
        'Central African Republic',
        'Cuba',
        'Haiti',
        'Iraq',
        'Russia',
        'Russian Federation',
        'Somalia',
        'Venezuela',
        'Zimbabwe',
    ];

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->back()->with('success', 'Welcome back to Neonwin!');
        }

        return redirect()->back()->withErrors([
            'email' => 'The provided credentials do not match our records.',
        ]);
    }

    public function register(Request $request, PromotionService $promotions)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'surname' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'phone' => 'required|string|max:30',
            'contact_channel' => ['nullable', Rule::in(['whatsapp', 'telegram'])],
            'contact_handle' => ['nullable', 'required_with:contact_channel', Rule::prohibitedIf(! $request->filled('contact_channel')), 'string', 'max:40', $request->input('contact_channel') === 'whatsapp' ? 'regex:/^\+[1-9][0-9]{6,14}$/D' : 'regex:/^@?[A-Za-z][A-Za-z0-9_]{4,31}$/D'],
            'contact_consent' => ['sometimes', 'boolean'],
            'account_emails_enabled' => ['sometimes', 'boolean'],
            'marketing_email_consent' => ['sometimes', 'boolean'],
            'date_of_birth' => [
                'required',
                'date',
                'before_or_equal:'.Carbon::now()->subYears(18)->format('Y-m-d'),
            ],
            'street' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'country' => [
                'required',
                'string',
                'max:100',
                Rule::notIn(self::RESTRICTED_COUNTRIES),
            ],
            'postcode' => 'required|string|max:30',
            'ref' => 'nullable|string|max:50',
            'promo_code' => ['nullable', 'string', 'max:40'],
            'terms' => 'accepted',
        ], [
            'date_of_birth.before_or_equal' => 'You must be at least 18 years of age to register.',
            'country.not_in' => 'Registration is not available in the selected country pursuant to regulatory compliance.',
            'terms.accepted' => 'You must agree to the Terms & Conditions and Privacy Policy.',
        ]);

        $referrer = null;
        if (! empty($validated['ref'] ?? null)) {
            $referrer = User::where('referral_code', trim($validated['ref']))->first();
        }

        [$user, $redemption] = DB::transaction(function () use ($validated, $referrer, $promotions): array {
            $user = User::create([
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'contact_channel' => $validated['contact_channel'] ?? null,
                'contact_handle' => $validated['contact_handle'] ?? null,
                'contact_consent_at' => ! empty($validated['contact_channel']) && ! empty($validated['contact_consent']) ? now() : null,
                'account_emails_enabled' => $validated['account_emails_enabled'] ?? true,
                'marketing_email_consent_at' => ! empty($validated['marketing_email_consent']) ? now() : null,
                'date_of_birth' => $validated['date_of_birth'],
                'street' => $validated['street'],
                'city' => $validated['city'],
                'country' => $validated['country'],
                'postcode' => $validated['postcode'],
                'referred_by_id' => $referrer?->id,
                'terms_accepted' => true,
                'password' => Hash::make($validated['password']),
                'game_balance' => 0.00, // No free welcome bonus
                'vault_balance' => 0.00,
                'vip_xp' => 0,
                'vip_level' => 1,
                'currency' => 'EUR',
                'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
            ]);

            $notice = RegistrationNotice::create(['user_id' => $user->id]);
            SendRegistrationNotice::dispatch($notice->id)->afterCommit();

            return [$user, $promotions->reserveRegistration($user, $validated['promo_code'] ?? null)];
        }, 3);

        if ($redemption && $redemption->snapshot['kind'] === 'free_spins') {
            $actor = $redemption->promotion->created_by ? User::find($redemption->promotion->created_by) : null;
            if ($actor?->is_admin) {
                try {
                    $promotions->issueFreeSpins($redemption, $actor);
                } catch (\Throwable $exception) {
                    Log::warning('Registration reward delivery needs review.', ['redemption_id' => $redemption->id]);
                }
            }
        }

        // Grant Welcome Recruit Cyber Crate
        UserCrate::create([
            'user_id' => $user->id,
            'type' => 'recruit',
            'name' => 'Cyber Recruit Welcome Crate',
            'tier' => 'bronze',
            'status' => 'pending',
        ]);

        if ($user->account_emails_enabled) {
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Throwable $e) {
                Log::warning('Welcome email could not be sent: '.$e->getMessage());
            }
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->back()->with('success', 'Account created successfully! Welcome to Neonwin.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('success', 'Logged out successfully');
    }
}
