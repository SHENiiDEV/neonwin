<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\User;
use App\Models\WalletEntry;
use App\Services\AdminWalletAdjustment;
use App\Services\EuroMoney;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminWalletController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'user_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $query = trim($filters['q'] ?? '');
        $users = $query === '' ? collect() : User::query()
            ->where(function ($builder) use ($query): void {
                $term = '%'.addcslashes($query, '%_').'%';
                $builder->where('email', 'like', $term)
                    ->orWhere('user_code', 'like', $term)
                    ->orWhere('name', 'like', $term);
            })
            ->orderBy('email')
            ->limit(12)
            ->get(['id', 'name', 'email', 'user_code', 'eur_balance_cents']);
        $selectedUser = isset($filters['user_id'])
            ? User::query()->findOrFail($filters['user_id'], ['id', 'name', 'email', 'user_code', 'eur_balance_cents'])
            : null;
        $entries = WalletEntry::query()
            ->with('user:id,name,email')
            ->whereIn('kind', ['admin_credit', 'admin_debit'])
            ->when($selectedUser, fn ($builder) => $builder->where('user_id', $selectedUser->id))
            ->latest('id')
            ->limit(15)
            ->get();
        $audits = AdminAudit::query()
            ->where('action', 'admin_wallet_adjusted')
            ->whereIn('target', $entries->pluck('reference'))
            ->get(['target', 'after'])
            ->keyBy('target');

        return Inertia::render('AdminWallets', [
            'query' => $query,
            'users' => $users,
            'selectedUser' => $selectedUser,
            'entries' => $entries->map(fn (WalletEntry $entry): array => [
                'id' => $entry->id,
                'user_id' => $entry->user_id,
                'email' => $entry->user->email,
                'delta_cents' => $entry->delta_cents,
                'balance_after_cents' => $entry->balance_after_cents,
                'reason' => $audits->get($entry->reference)?->after['reason'] ?? null,
                'created_at' => $entry->created_at,
            ]),
        ]);
    }

    public function store(Request $request, AdminWalletAdjustment $adjustment): RedirectResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'target_email' => ['required', 'email', 'max:255'],
            'direction' => ['required', Rule::in(['credit', 'debit'])],
            'amount' => ['required', 'regex:/^[1-9][0-9]{0,5}(\.[0-9]{1,2})?$/D'],
            'reason' => ['required', 'string', 'min:10', 'max:300'],
            'request_id' => ['required', 'uuid'],
            'confirmed' => ['accepted'],
        ]);
        $target = User::findOrFail($data['user_id']);
        if (mb_strtolower(trim($data['target_email'])) !== mb_strtolower($target->email)) {
            throw ValidationException::withMessages(['target_email' => 'The confirmation email does not match the selected player.']);
        }
        $reason = trim($data['reason']);
        if (mb_strlen($reason) < 10) {
            throw ValidationException::withMessages(['reason' => 'Enter a meaningful reason of at least 10 characters.']);
        }

        $cents = EuroMoney::cents($data['amount']);
        $delta = $data['direction'] === 'credit' ? $cents : -$cents;
        $adjustment->adjust($request->user(), $target, $delta, $reason, $data['request_id']);

        return back()->with('success', 'EUR wallet adjustment recorded for '.$target->email.'.');
    }
}
