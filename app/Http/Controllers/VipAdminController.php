<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\Game;
use App\Models\VipQuest;
use App\Models\VipTier;
use App\Services\EuroMoney;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class VipAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminVip', ['tiers' => VipTier::orderBy('level')->get(), 'quests' => VipQuest::orderBy('id')->get()]);
    }

    public function tiers(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tiers' => ['required', 'array', 'size:10'],
            'tiers.*.level' => ['required', 'integer', 'between:1,10', 'distinct'],
            'tiers.*.name' => ['required', 'string', 'max:100'],
            'tiers.*.threshold' => ['required', 'numeric', 'decimal:0,2', 'between:0,10000000'],
            'tiers.*.bonus' => ['required', 'numeric', 'decimal:0,2', 'between:0,1000'],
            'tiers.*.checkin' => ['required', 'numeric', 'decimal:0,2', 'between:0,1'],
            'tiers.*.daily_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0,3'],
            'tiers.*.weekly_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0,3'],
            'tiers.*.monthly_percent' => ['required', 'numeric', 'decimal:0,2', 'between:0,3'],
        ]);
        $rows = collect($data['tiers'])->sortBy('level')->values();
        $previous = -1;
        foreach ($rows as $i => $row) {
            $threshold = EuroMoney::cents($row['threshold']);
            $rate = EuroMoney::cents($row['daily_percent']) + EuroMoney::cents($row['weekly_percent']) + EuroMoney::cents($row['monthly_percent']);
            if (($i === 0 && ($threshold !== 0 || EuroMoney::cents($row['bonus']) !== 0)) || $threshold <= $previous || $rate > 500) {
                throw ValidationException::withMessages(['tiers' => 'Level 1 must start at €0 with no unlock bonus. Thresholds must increase and combined rakeback must not exceed 5%.']);
            }
            $previous = $threshold;
        }
        DB::transaction(function () use ($rows, $request): void {
            $before = VipTier::orderBy('level')->lockForUpdate()->get()->toArray();
            foreach ($rows as $row) {
                VipTier::where('level', $row['level'])->update(['name' => $row['name'], 'threshold_cents' => EuroMoney::cents($row['threshold']), 'bonus_cents' => EuroMoney::cents($row['bonus']), 'checkin_cents' => EuroMoney::cents($row['checkin']), 'daily_bps' => EuroMoney::cents($row['daily_percent']), 'weekly_bps' => EuroMoney::cents($row['weekly_percent']), 'monthly_bps' => EuroMoney::cents($row['monthly_percent'])]);
            }
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'vip_tiers_saved', 'target' => 'vip', 'before' => $before, 'after' => VipTier::orderBy('level')->get()->toArray()]);
        });

        return back()->with('success', 'VIP tiers saved. Existing accrued rewards retain their amounts.');
    }

    public function quest(Request $request, ?VipQuest $quest = null): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'], 'kind' => ['required', 'in:wager,spins'],
            'provider_code' => ['nullable', 'string', 'max:100'], 'game_code' => ['nullable', 'string', 'max:255'],
            'target' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,1000000'],
            'min_bet' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,1000'],
            'reward' => ['required', 'numeric', 'decimal:0,2', 'between:0.01,100'], 'enabled' => ['required', 'boolean'],
        ]);
        if ($data['kind'] === 'spins' && (float) $data['target'] !== (float) (int) $data['target']) {
            throw ValidationException::withMessages(['target' => 'Paid-round targets must be whole numbers.']);
        }
        if (! empty($data['provider_code']) || ! empty($data['game_code'])) {
            $game = Game::where('provider_code', $data['provider_code'] ?? '')->where('game_code', $data['game_code'] ?? '')->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->first();
            if (! $game || ($data['kind'] === 'spins' && $game->category !== 'slots')) {
                throw ValidationException::withMessages(['game_code' => 'Select an available game; paid-round quests require a slot.']);
            }
        }
        DB::transaction(function () use ($quest, $data, $request): void {
            $before = $quest?->toArray();
            $values = ['name' => $data['name'], 'kind' => $data['kind'], 'provider_code' => $data['provider_code'] ?? null, 'game_code' => $data['game_code'] ?? null, 'target' => $data['kind'] === 'wager' ? EuroMoney::cents($data['target']) : (int) $data['target'], 'min_bet_cents' => EuroMoney::cents($data['min_bet']), 'reward_cents' => EuroMoney::cents($data['reward']), 'enabled' => $data['enabled']];
            $saved = $quest ?? new VipQuest;
            $saved->fill($values)->save();
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'vip_quest_saved', 'target' => (string) $saved->id, 'before' => $before, 'after' => $saved->toArray()]);
        });

        return back()->with('success', 'Quest saved. Existing daily progress keeps its original terms.');
    }
}
