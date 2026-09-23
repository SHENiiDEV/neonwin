<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\Game;
use App\Models\LiveCommunityWin;
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

        // Fetch providers that have games
        $providers = Provider::where('status', true)
            ->has('games')
            ->withCount('games')
            ->get()
            ->sortByDesc('games_count')
            ->values();

        $gamesQuery = Game::where('status', true);

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

        $liveGames = Game::where('status', true)->where('category', 'live')->take(8)->get();
        $crashGames = Game::where('status', true)->where('category', 'crash')->take(8)->get();
        $fishingGames = Game::where('status', true)->where('category', 'fishing')->take(8)->get();
        $allGames = $gamesQuery->take(24)->get();

        $tournaments = Tournament::where('status', 'active')->orderBy('ends_at', 'asc')->get();
        $highlights = LiveCommunityWin::latest()->take(10)->get();
        $chatMessages = ChatMessage::latest()->take(20)->get()->reverse()->values();

        return Inertia::render('Home', [
            'providers' => $providers,
            'popularGames' => $popularGames,
            'liveGames' => $liveGames,
            'crashGames' => $crashGames,
            'fishingGames' => $fishingGames,
            'allGames' => $allGames,
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
