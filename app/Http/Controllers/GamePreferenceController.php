<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\UserFavorite;
use App\Models\UserRecentGame;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GamePreferenceController extends Controller
{
    public function toggleFavorite(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_code' => ['required', 'string'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'error', 'message' => 'Please log in to save favorites.'], 401);
        }

        $existing = UserFavorite::where('user_id', $user->id)
            ->where('game_code', $validated['game_code'])
            ->first();

        if ($existing) {
            $existing->delete();
            $isFavorite = false;
        } else {
            UserFavorite::create([
                'user_id' => $user->id,
                'game_code' => $validated['game_code'],
            ]);
            $isFavorite = true;
        }

        $favoriteCodes = UserFavorite::where('user_id', $user->id)->pluck('game_code')->toArray();

        return response()->json([
            'status' => 'success',
            'is_favorite' => $isFavorite,
            'favorites' => $favoriteCodes,
        ]);
    }

    public function getFavorites(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['favorites' => [], 'games' => []]);
        }

        $favoriteCodes = UserFavorite::where('user_id', $user->id)->pluck('game_code')->toArray();
        $games = Game::whereIn('game_code', $favoriteCodes)->get();

        return response()->json([
            'favorites' => $favoriteCodes,
            'games' => $games,
        ]);
    }

    public function logRecent(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'game_code' => ['required', 'string'],
        ]);

        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'guest']);
        }

        UserRecentGame::updateOrCreate(
            ['user_id' => $user->id, 'game_code' => $validated['game_code']],
            ['last_played_at' => now()]
        );

        return response()->json(['status' => 'success']);
    }

    public function toggleSound(): JsonResponse
    {
        $user = Auth::user();
        if (! $user) {
            return response()->json(['status' => 'guest']);
        }

        $user->update(['sound_enabled' => ! $user->sound_enabled]);

        return response()->json([
            'status' => 'success',
            'sound_enabled' => (bool) $user->sound_enabled,
        ]);
    }
}
