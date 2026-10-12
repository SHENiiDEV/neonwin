<?php

namespace App\Http\Controllers;

use App\Models\AdminAudit;
use App\Models\Deposit;
use App\Models\Game;
use App\Models\GameSession;
use App\Models\GameTransaction;
use App\Models\KycSubmission;
use App\Models\NexusOperation;
use App\Models\OperationalAlert;
use App\Models\Provider;
use App\Models\User;
use App\Models\WalletEntry;
use App\Services\FlexrixService;
use App\Services\GamingService;
use App\Services\NexusGgrService;
use App\Services\NexusOperations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate(['view' => ['nullable', 'in:overview,users,kyc,deposits,transactions,sessions,ledger,rewards,providers,alerts,audit'], 'q' => ['nullable', 'string', 'max:100'], 'status' => ['nullable', 'string', 'max:40'], 'user_id' => ['nullable', 'integer'], 'provider' => ['nullable', 'string', 'max:100'], 'game_type' => ['nullable', 'in:slot,live,SB,MN,FH'], 'start' => ['nullable', 'date'], 'end' => ['nullable', 'date', 'after_or_equal:start']]);
        $view = $filters['view'] ?? 'overview';
        $model = match ($view) {
            'users' => User::class, 'kyc' => KycSubmission::class, 'deposits' => Deposit::class, 'sessions' => GameSession::class,
            'ledger' => WalletEntry::class, 'rewards' => NexusOperation::class, 'providers' => Provider::class, 'alerts' => OperationalAlert::class,
            'audit' => AdminAudit::class, default => GameTransaction::class,
        };
        $query = $model::query();
        if ($model === GameTransaction::class) {
            $query->where('currency', 'EUR');
        }
        if (! empty($filters['q'])) {
            $term = '%'.addcslashes($filters['q'], '%_').'%';
            if ($model === User::class) {
                $query->where(fn ($q) => $q->where('name', 'like', $term)->orWhere('email', 'like', $term)->orWhere('user_code', 'like', $term));
            } elseif ($model === Provider::class) {
                $query->where(fn ($q) => $q->where('code', 'like', $term)->orWhere('name', 'like', $term));
            } elseif (in_array($model, [AdminAudit::class, OperationalAlert::class], true)) {
                $query->where($model === AdminAudit::class ? 'target' : 'reference', 'like', $term);
            } else {
                $query->whereHas('user', fn ($q) => $q->where('user_code', 'like', $term));
            }
        }
        if (! empty($filters['status']) && ! in_array($model, [User::class, GameTransaction::class, WalletEntry::class, AdminAudit::class], true)) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['user_id']) && ! in_array($model, [Provider::class, AdminAudit::class, OperationalAlert::class], true)) {
            $query->where($model === User::class ? 'id' : 'user_id', $filters['user_id']);
        }
        if (! empty($filters['provider']) && in_array($model, [GameTransaction::class, GameSession::class, NexusOperation::class], true)) {
            $query->where('provider_code', $filters['provider']);
        }
        if (! empty($filters['game_type']) && $model === GameTransaction::class) {
            $query->where('game_type', $filters['game_type']);
        }
        foreach (['start' => '>=', 'end' => '<='] as $key => $operator) {
            if (! empty($filters[$key])) {
                $query->whereDate('created_at', $operator, $filters[$key]);
            }
        }
        $columns = match ($model) {
            User::class => ['id', 'name', 'email', 'user_code', 'eur_balance_cents', 'eur_vault_cents', 'is_admin', 'created_at'],
            GameSession::class => ['id', 'user_id', 'provider_code', 'game_code', 'status', 'last_activity_at', 'created_at'],
            GameTransaction::class => ['id', 'user_id', 'provider_code', 'game_code', 'game_type', 'remote_transaction_id', 'round_id', 'type', 'bet_amount', 'win_amount', 'balance_before', 'balance_after', 'currency', 'created_at'],
            default => ['*'],
        };
        $records = $query->latest('id')->paginate(20, $columns)->withQueryString();
        if ($model === KycSubmission::class) {
            $records->through(fn ($submission) => array_merge($submission->toArray(), ['has_document' => (bool) $submission->document_path, 'has_selfie' => (bool) $submission->selfie_path]));
        }
        $eur = GameTransaction::where('currency', 'EUR');
        $stats = [
            'users' => User::count(), 'wallet_cents' => User::sum('eur_balance_cents'), 'pending_kyc' => KycSubmission::where('status', 'pending')->count(),
            'confirmed_deposits_cents' => Deposit::where('status', 'confirmed')->sum('amount_cents'), 'open_alerts' => OperationalAlert::where('status', 'open')->count(),
            'bets' => (clone $eur)->sum('bet_amount'), 'wins' => (clone $eur)->sum('win_amount'), 'last_transaction_at' => (clone $eur)->max('created_at'),
            'recent_activity' => GameSession::where('last_activity_at', '>=', now()->subMinutes(5))->distinct('user_id')->count('user_id'),
        ];
        $stale = NexusOperation::whereIn('status', ['sending', 'canceling'])->where('updated_at', '<', now()->subMinutes(2))->count();
        $expiring = NexusOperation::where('method', 'tour_create')->whereNotNull('active_tour_key')->where('expires_at', '<', now())->count();

        return Inertia::render('Admin', ['view' => $view, 'records' => $records, 'stats' => $stats, 'filters' => $filters, 'updatedAt' => now()->toISOString(),
            'agentBalance' => Cache::get('nexus:admin:balance'), 'remoteLog' => $request->session()->get('remote_log'),
            'gamingProvider' => config('gaming.provider'), 'flexrixStatus' => ['casino' => app(FlexrixService::class)->configured('casino'), 'sportsbook' => app(FlexrixService::class)->configured('sportsbook') && FlexrixService::httpsUrl(config('gaming.flexrix.sportsbook_url'))],
            'capabilities' => ['eur' => (bool) config('nexus.eur_agent_confirmed'), 'rtp' => config('gaming.provider') === 'nexus' && (bool) config('nexus.rtp_enabled'), 'rewards' => config('gaming.provider') === 'nexus' && (bool) config('nexus.rewards_enabled'), 'agent_type' => config('nexus.agent_type'), 'rtp_providers' => config('nexus.rtp_providers'), 'stale_operations' => $stale, 'expired_unconfirmed_tours' => $expiring],
            'rewardGames' => Game::where('provider_code', 'PRAGMATIC')->where('status', true)->orderBy('name')->get(['game_code', 'name', 'bet_levels']),
        ]);
    }

    public function operate(Request $request, NexusOperations $operations): RedirectResponse
    {
        abort_unless(config('gaming.provider') === 'nexus', 409, 'Nexus is retired. Use the Flexrix hub for provider operations.');
        $data = $request->validate([
            'request_id' => ['required', 'uuid'], 'method' => ['required', 'in:control_rtp,control_users_rtp,bonus_create,tour_create'],
            'user_codes' => ['required', 'array', 'min:1', 'max:100'], 'user_codes.*' => ['required', 'string', 'max:255', 'distinct', 'regex:/^\S+$/'],
            'provider_code' => ['required_unless:method,control_users_rtp', 'nullable', 'string', 'max:100'], 'rtp' => ['required_if:method,control_rtp,control_users_rtp', 'nullable', 'numeric', 'between:0,999'],
            'game_code' => ['required_if:method,bonus_create,tour_create', 'nullable', 'string', 'max:255'],
            'amount' => ['required_if:method,bonus_create,tour_create', 'nullable', 'numeric', 'decimal:0,2', 'min:0.01', 'max:100000000'],
            'max_bet' => ['required_if:method,bonus_create', 'nullable', 'integer', 'min:1'], 'bet_level' => ['required_if:method,tour_create', 'nullable', 'integer', 'min:1'],
            'spin_count' => ['required_if:method,tour_create', 'nullable', 'integer', 'between:1,1000'], 'expiration_time' => ['required_if:method,tour_create', 'nullable', 'date', 'after:now'],
        ]);
        $operation = $operations->create($request->user(), $data);

        return back()->with(in_array($operation->status, ['succeeded', 'waiting', 'active', 'running', 'finished'], true) ? 'success' : 'error', 'Request '.$operation->external_id.': '.$operation->status.'. '.($operation->result['msg'] ?? 'Check operational requests before retrying.'));
    }

    public function cancel(Request $request, NexusOperation $operation, NexusOperations $operations): RedirectResponse
    {
        abort_unless(config('gaming.provider') === 'nexus', 409, 'Nexus is retired. Use the Flexrix hub for provider operations.');
        $operation = $operations->cancel($request->user(), $operation);

        return back()->with($operation->status === 'canceled' ? 'success' : 'error', 'Tour '.$operation->external_id.': '.$operation->status.'. '.($operation->result['msg'] ?? ''));
    }

    public function sync(Request $request, GamingService $gaming): RedirectResponse
    {
        $data = $request->validate(['provider_code' => ['required', 'string', 'max:100', 'regex:/^[A-Z0-9_]+$/']]);
        $result = $gaming->sync($data['provider_code']);
        AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'catalog_sync', 'target' => $data['provider_code'], 'after' => $result]);

        return back()->with($result['errors'] ? 'error' : 'success', $result['errors'] ? 'Catalog sync could not be fully confirmed. Existing records were retained.' : 'Provider catalog synchronized.');
    }

    public function balance(Request $request, NexusGgrService $nexus): RedirectResponse
    {
        abort_unless(config('gaming.provider') === 'nexus', 409, 'Nexus is retired. Use the Flexrix hub for provider operations.');
        $result = $nexus->getMoneyInfo();
        $balance = ['ok' => NexusGgrService::successful($result), 'agent_balance' => $result['agent']['balance'] ?? $result['agent_balance'] ?? null, 'message' => $result['msg'], 'checked_at' => now()->toISOString()];
        Cache::put('nexus:admin:balance', $balance, 300);

        return back()->with($balance['ok'] ? 'success' : 'error', $balance['message']);
    }

    public function gameLog(Request $request, NexusGgrService $nexus): RedirectResponse
    {
        abort_unless(config('gaming.provider') === 'nexus', 409, 'Nexus is retired. Use the Flexrix hub for provider operations.');
        $data = $request->validate(['user_code' => ['nullable', 'string', 'max:255'], 'game_type' => ['required', 'in:slot,live,SB'], 'start' => ['required', 'date_format:Y-m-d H:i:s'], 'end' => ['required', 'date_format:Y-m-d H:i:s', 'after_or_equal:start'], 'page' => ['required', 'integer', 'min:0'], 'perPage' => ['required', 'integer', 'between:1,1000']]);
        $result = $nexus->getGameLog($data['user_code'] ?? null, $data['game_type'], $data['start'], $data['end'], $data['page'], $data['perPage']);
        $safe = ['ok' => NexusGgrService::successful($result), 'message' => $result['msg'], 'page' => $result['page'] ?? $data['page'], 'total_count' => $result['total_count'] ?? 0,
            'records' => array_map(fn ($row) => array_intersect_key($row, array_flip(['history_id', 'user_code', 'provider_code', 'game_code', 'type', 'bet_money', 'win_money', 'txn_id', 'txn_type', 'created_at'])), $result[$data['game_type']] ?? [])];
        AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'game_log_read', 'target' => $data['game_type'], 'after' => ['page' => $data['page'], 'status' => $result['status']]]);

        return back()->with('remote_log', $safe)->with($safe['ok'] ? 'success' : 'error', $safe['message']);
    }

    public function resolve(Request $request, OperationalAlert $alert): RedirectResponse
    {
        $data = $request->validate(['note' => ['required', 'string', 'min:10', 'max:1000']]);
        DB::transaction(function () use ($alert, $request, $data): void {
            $alert->update(['status' => 'resolved', 'resolved_by' => $request->user()->id, 'resolved_at' => now()]);
            AdminAudit::create(['actor_id' => $request->user()->id, 'action' => 'alert_resolved', 'target' => (string) $alert->id, 'after' => $data]);
        });

        return back()->with('success', 'Reconciliation note saved. Wallet balances were not changed.');
    }
}
