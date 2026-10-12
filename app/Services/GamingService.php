<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;

class GamingService
{
    public function __construct(private NexusGgrService $nexus, private FlexrixService $flexrix) {}

    public function launch(User $user, Game $game, string $language): array
    {
        if ($game->integration !== config('gaming.provider')) {
            return ['message' => 'This game belongs to a retired catalogue.'];
        }

        return config('gaming.provider') === 'flexrix'
            ? $this->flexrix->launch($user, $game, $language)
            : $this->nexus->launchGame($user, $game->game_code, $game->provider_code, $language);
    }

    public function sync(string $provider = 'all'): array
    {
        return config('gaming.provider') === 'flexrix' ? $this->flexrix->sync() : $this->nexus->syncGames($provider);
    }
}
