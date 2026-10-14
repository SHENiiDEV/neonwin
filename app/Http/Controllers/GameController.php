<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Provider;
use App\Models\User;
use App\Services\DeviceSupport;
use App\Services\FlexrixService;
use App\Services\GamingService;
use App\Services\NexusGgrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function __construct(private GamingService $gaming) {}

    /**
     * Launch and play game view (GET /game/{slug})
     */
    public function play(Request $request, string $slug)
    {
        $game = Game::where('integration', config('gaming.provider'))
            ->where(fn ($query) => $query->where('slug', $slug)->orWhere('game_code', $slug))
            ->firstOrFail();

        // Require authenticated user
        $user = Auth::user();
        if (! $user) {
            return redirect()->route('home')->with('error', 'Please log in or create an account to play games.');
        }

        abort_unless($game->status && $game->provider?->status, 503, 'This game is under maintenance.');
        abort_unless(! DeviceSupport::isMobile($request) || $game->mobile_supported, 403, 'This game is available on desktop only.');

        $launchResult = $this->gaming->launch($user, $game, (string) $request->query('lang', 'en'));

        $relatedGames = Game::where('provider_code', $game->provider_code)
            ->where('id', '!=', $game->id)
            ->where('status', true)
            ->when(DeviceSupport::isMobile($request), fn ($query) => $query->where('mobile_supported', true))
            ->inRandomOrder()
            ->limit(6)
            ->get();

        if ($relatedGames->count() < 6) {
            $fallbackGames = Game::where('integration', config('gaming.provider'))->where('category', $game->category)
                ->where('id', '!=', $game->id)
                ->whereNotIn('id', $relatedGames->pluck('id'))
                ->where('status', true)
                ->when(DeviceSupport::isMobile($request), fn ($query) => $query->where('mobile_supported', true))
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

    public function sportsbook(Request $request): Response
    {
        if (config('gaming.provider') === 'flexrix') {
            $result = app(FlexrixService::class)->sportsbook($request->user(), (string) $request->query('lang', 'en'));

            return Inertia::render('Sportsbook', ['launchUrl' => $result['launch_url'] ?? null, 'message' => $result['message'] ?? '']);
        }
        $provider = Provider::where('code', 'SPORTSBOOK')->first();
        $result = $provider?->status ? app(NexusGgrService::class)->launchGame($request->user(), '', 'SPORTSBOOK') : ['message' => 'Sportsbook is currently unavailable. Please try again later.'];

        return Inertia::render('Sportsbook', ['launchUrl' => $result['launch_url'] ?? null, 'message' => $result['message'] ?? 'Sportsbook launch failed.']);
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

        $games = Game::where('integration', config('gaming.provider'))->where('status', true)->whereHas('provider', fn ($query) => $query->where('status', true))
            ->when(DeviceSupport::isMobile($request), fn ($query) => $query->where('mobile_supported', true))
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
