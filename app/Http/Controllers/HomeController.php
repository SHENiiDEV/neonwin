<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Game;
use App\Models\GameTransaction;
use App\Models\Provider;
use App\Models\Tournament;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function index(Request $request): Response
    {
        $category = $request->query('category', 'all');
        $provider = $request->query('provider', 'all');
        $search = $request->query('search', '');

        $providers = Provider::where('integration', config('gaming.provider'))->where('status', true)
            ->whereHas('games', fn ($query) => $query->where('status', true)->where('integration', config('gaming.provider')))
            ->withCount(['games' => fn ($query) => $query->where('status', true)->where('integration', config('gaming.provider'))])
            ->orderByDesc('games_count')
            ->orderBy('name')
            ->get();

        $gamesQuery = Game::where('integration', config('gaming.provider'))->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true));

        if ($category !== 'all' && ! empty($category)) {
            if ($category === 'popular') {
                $gamesQuery->where('is_popular', true);
            } else {
                $gamesQuery->where('category', $category);
            }
        }

        if ($provider !== 'all' && ! empty($provider)) {
            $gamesQuery->where('provider_code', strtoupper($provider));
        }

        if (! empty($search)) {
            $gamesQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('provider_code', 'like', "%{$search}%");
            });
        }

        $popularGames = (clone $gamesQuery)->where('is_popular', true)->take(12)->get();
        if ($popularGames->isEmpty()) {
            $popularGames = (clone $gamesQuery)->take(12)->get();
        }

        $liveGames = Game::where('integration', config('gaming.provider'))->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->where('category', 'live')->take(8)->get();
        $crashGames = Game::where('integration', config('gaming.provider'))->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->where('category', 'crash')->take(8)->get();
        $fishingGames = Game::where('integration', config('gaming.provider'))->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))->where('category', 'fishing')->take(8)->get();
        $allGames = $gamesQuery->orderBy('name')->orderBy('id')->paginate(36)->withQueryString()->appends(['browse' => 'all']);

        $tournaments = Tournament::where('status', 'active')->orderBy('ends_at', 'asc')->get();
        $highlights = GameTransaction::where('currency', 'EUR')->where('win_amount', '>', 0)->latest('id')->limit(10)->get(['id', 'game_code', 'bet_amount', 'win_amount', 'created_at'])->map(fn ($transaction) => [
            'id' => $transaction->id, 'user_name' => 'Player', 'game_name' => $transaction->game_code, 'game_image' => '',
            'bet_amount' => $transaction->bet_amount, 'win_amount' => $transaction->win_amount,
            'multiplier' => (float) $transaction->bet_amount > 0 ? round((float) $transaction->win_amount / (float) $transaction->bet_amount, 2) : 0,
        ]);
        $chatMessages = ChatMessage::latest()->take(20)->get()->reverse()->values();

        return Inertia::render('Home', [
            'providers' => $providers,
            'popularGames' => $popularGames,
            'liveGames' => $liveGames,
            'crashGames' => $crashGames,
            'fishingGames' => $fishingGames,
            'allGames' => $allGames->items(),
            'catalogue' => ['total' => $allGames->total(), 'page' => $allGames->currentPage(), 'last_page' => $allGames->lastPage(), 'next' => $allGames->nextPageUrl(), 'previous' => $allGames->previousPageUrl()],
            'tournaments' => $tournaments,
            'highlights' => $highlights,
            'chatMessages' => $chatMessages,
            'filters' => [
                'category' => $category,
                'provider' => $provider,
                'search' => $search,
            ],
        ]);
    }
}
