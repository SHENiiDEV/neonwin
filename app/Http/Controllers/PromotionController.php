<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\Game;
use App\Models\Promotion;
use App\Models\PromotionRedemption;
use App\Services\EuroMoney;
use App\Services\PromotionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminPromotions', [
            'promotions' => Promotion::withCount('redemptions')->orderBy('id')->get(),
            'redemptions' => PromotionRedemption::with('user:id,email,user_code')->latest()->paginate(20),
            'games' => Game::where('provider_code', 'PRAGMATIC')->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->orderBy('name')->get(['game_code', 'name', 'bet_levels']),
            'rewardDeliveryEnabled' => config('gaming.provider') === 'nexus' && (bool) config('nexus.eur_agent_confirmed') && (bool) config('nexus.rewards_enabled'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->save($request, new Promotion);
    }

    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        return $this->save($request, $promotion);
    }

    private function save(Request $request, Promotion $promotion): RedirectResponse
    {
        $request->merge(['code' => $request->filled('code') ? strtoupper(trim($request->string('code')->toString())) : null]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'kind' => ['required', Rule::in($promotion->kind === 'first_deposit' ? ['first_deposit'] : ['deposit_bonus', 'free_spins'])],
            'code' => [$promotion->kind === 'first_deposit' ? 'nullable' : 'required', 'string', 'min:3', 'max:40', 'regex:/^[A-Z0-9_-]+$/', Rule::unique('promotions')->ignore($promotion->id)],
            'enabled' => ['required', 'boolean'], 'percent' => ['required', 'numeric', 'decimal:0,2', 'between:0,1000'],
            'min_deposit' => ['required', 'numeric', 'decimal:0,2', 'between:0,100000000'], 'max_bonus' => ['nullable', 'numeric', 'decimal:0,2', 'between:0.01,100000000'],
            'redemption_limit' => ['nullable', 'integer', 'min:1', 'max:1000000'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'terms' => ['required', 'string', 'max:2000'],
            'game_code' => ['required_if:kind,free_spins', 'nullable', 'string', 'max:255'], 'bet_level' => ['required_if:kind,free_spins', 'nullable', 'integer', 'min:1'],
            'spin_count' => ['required_if:kind,free_spins', 'nullable', 'integer', 'between:1,1000'], 'amount' => ['required_if:kind,free_spins', 'nullable', 'numeric', 'decimal:0,2', 'between:0.01,100000000'],
            'expiry_hours' => ['required_if:kind,free_spins', 'nullable', 'integer', 'between:1,720'],
        ]);
        if ($promotion->exists && $data['kind'] !== $promotion->kind) {
            return back()->withErrors(['kind' => 'Create a new code to change its reward type. Existing entitlements retain their original terms.']);
        }
        $reward = null;
        if ($data['kind'] === 'free_spins') {
            $game = Game::where('provider_code', 'PRAGMATIC')->where('game_code', $data['game_code'])->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->first();
            if (! $game || ! isset(($game->bet_levels ?? [])[$data['bet_level']])) {
                return back()->withErrors(['bet_level' => 'Select a synchronized, available PRAGMATIC game and its supplied bet level.']);
            }
            $reward = array_intersect_key($data, array_flip(['game_code', 'bet_level', 'spin_count', 'amount', 'expiry_hours']));
        }
        DB::transaction(function () use ($request, $promotion, $data, $reward): void {
            $locked = $promotion->exists ? Promotion::whereKey($promotion->id)->lockForUpdate()->firstOrFail() : $promotion;
            $before = $locked->exists ? $locked->toArray() : null;
            $locked->fill([
                'name' => $data['name'], 'kind' => $data['kind'], 'code' => $data['kind'] === 'first_deposit' ? null : $data['code'], 'enabled' => $data['enabled'],
                'percent_bps' => EuroMoney::cents($data['percent']), 'min_deposit_cents' => EuroMoney::cents($data['min_deposit']),
                'max_bonus_cents' => isset($data['max_bonus']) ? EuroMoney::cents($data['max_bonus']) : null,
                'redemption_limit' => $data['redemption_limit'] ?? null, 'starts_at' => $data['starts_at'] ?? null, 'ends_at' => $data['ends_at'] ?? null,
                'terms' => $data['terms'], 'reward' => $reward,
            ]);
            if (! $locked->exists) {
                $locked->created_by = $request->user()->id;
            }
            $locked->save();
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'promotion_saved', 'target' => (string) $locked->id, 'before' => $before, 'after' => $locked->toArray()]);
        });

        return back()->with('success', 'Promotion saved. Reserved registration offers retain their original terms.');
    }

    public function issue(Request $request, PromotionRedemption $redemption, PromotionService $promotions): RedirectResponse
    {
        $result = $promotions->issueFreeSpins($redemption, $request->user());
        AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'promotion_delivery_checked', 'target' => (string) $redemption->id, 'after' => $result->only(['status', 'message', 'request_id'])]);

        return back()->with($result->status === 'issued' ? 'success' : 'error', $result->message ?? 'Delivery remains pending; check the Nexus operation before retrying.');
    }
}
