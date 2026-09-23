<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\User;
use App\Services\NexusGgrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GameController extends Controller
{
    protected NexusGgrService $nexusService;

    public function __construct(NexusGgrService $nexusService)
    {
        $this->nexusService = $nexusService;
    }

    /**
     * Launch and play game view (GET /game/{slug})
     */
    public function play(Request $request, string $slug)
    {
        $game = Game::where('slug', $slug)
            ->orWhere('game_code', $slug)
            ->firstOrFail();

        // Get authenticated user or create a temporary demo user session
        $user = Auth::user();
        if (! $user) {
            $user = User::firstOrCreate(
                ['email' => 'player@neonwin.com'],
                [
                    'name' => 'CyberPlayer',
                    'password' => bcrypt('secret123'),
                    'game_balance' => 1500.00,
                    'vip_xp' => 1250,
                    'vip_level' => 2,
                    'avatar' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80',
                ]
            );
            Auth::login($user);
        }

        $launchResult = $this->nexusService->launchGame(
            $user,
            $game->game_code,
            $game->provider_code,
            $request->query('lang', 'en')
        );

        $relatedGames = Game::where('provider_code', $game->provider_code)
            ->where('id', '!=', $game->id)
            ->where('status', true)
            ->inRandomOrder()
            ->limit(6)
            ->get();

        if ($relatedGames->count() < 6) {
            $fallbackGames = Game::where('category', $game->category)
                ->where('id', '!=', $game->id)
                ->whereNotIn('id', $relatedGames->pluck('id'))
                ->where('status', true)
                ->inRandomOrder()
                ->limit(6 - $relatedGames->count())
                ->get();
            $relatedGames = $relatedGames->merge($fallbackGames);
        }

        return Inertia::render('GamePlayer', [
            'game' => $game,
            'launchUrl' => $launchResult['launch_url'] ?? '',
            'isMock' => $launchResult['is_mock'] ?? false,
            'message' => $launchResult['message'] ?? '',
            'relatedGames' => $relatedGames,
        ]);
    }

    /**
     * Interactive Mock Game Frame for testing and demo gameplay
     */
    public function mockFrame(Request $request)
    {
        $provider = strtoupper($request->query('provider', 'PRAGMATIC'));
        $gameCode = $request->query('game', 'vs20olympgate');
        $userCode = $request->query('user', '');

        $game = Game::where('game_code', $gameCode)->first();
        $gameTitle = $game ? $game->name : ucwords(str_replace(['_', '-'], ' ', $gameCode));
        $category = $game ? $game->category : 'slots';

        return view('mock-game', [
            'provider' => $provider,
            'gameCode' => $gameCode,
            'gameTitle' => $gameTitle,
            'category' => $category,
            'userCode' => $userCode,
        ]);
    }

    /**
     * Live search games API (GET /api/games/search?q=query)
     */
    public function search(Request $request)
    {
        $query = trim($request->query('q', ''));
        if (strlen($query) < 2) {
            return response()->json(['games' => [], 'count' => 0]);
        }

        $safeQuery = addcslashes($query, '%_');

        $games = Game::where('status', true)
            ->where(function ($q) use ($safeQuery) {
                $q->where('name', 'like', "%{$safeQuery}%")
                    ->orWhere('slug', 'like', "%{$safeQuery}%")
                    ->orWhere('game_code', 'like', "%{$safeQuery}%")
                    ->orWhere('provider_code', 'like', "%{$safeQuery}%");
            })
            ->limit(10)
            ->get(['id', 'name', 'slug', 'game_code', 'provider_code', 'category', 'banner_url', 'rtp', 'is_popular']);

        return response()->json([
            'games' => $games,
            'count' => $games->count(),
        ]);
    }
}
