<?php

namespace App\Services;

use App\Models\Game;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NexusGgrService
{
    protected string $baseUrl;

    protected string $agentCode;

    protected string $agentToken;

    protected string $agentSecret;

    protected bool $mockMode;

    protected string $lobbyUrl;

    public function __construct()
    {
        $this->baseUrl = config('nexus.base_url', 'https://api.nexusggr.eu');
        $this->agentCode = config('nexus.agent_code', 'neonwin');
        $this->agentToken = config('nexus.agent_token', '87f3f75c9501543d36bb0ebfcb473a72');
        $this->agentSecret = config('nexus.agent_secret', '5562754eb33e45937673a6bacdd8be9d');
        $this->mockMode = (bool) config('nexus.mock_mode', false);
        $this->lobbyUrl = config('nexus.lobby_url', config('app.url', 'https://neonwin.co.uk'));
    }

    /**
     * Send standard POST request to Nexus API
     */
    protected function post(array $payload): ?array
    {
        $body = array_merge([
            'agent_code' => $this->agentCode,
            'agent_token' => $this->agentToken,
            'agent_secret' => $this->agentSecret,
        ], $payload);

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, $body);

            if ($response->successful()) {
                return $response->json();
            }

            Log::warning('Nexus API response error', [
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload,
            ]);
        } catch (\Exception $e) {
            Log::error('Nexus API connection exception: '.$e->getMessage());
        }

        return null;
    }

    /**
     * 1. Fetch Providers list (method: provider_list)
     */
    public function fetchProviders(): array
    {
        $response = $this->post([
            'method' => 'provider_list',
        ]);

        if ($response && isset($response['providers']) && is_array($response['providers'])) {
            return $response['providers'];
        }

        if ($response && isset($response['data']) && is_array($response['data'])) {
            return $response['data'];
        }

        // Official Social Casino Provider List fallback
        return [
            ['code' => 'PRAGMATIC', 'name' => 'Pragmatic Slot', 'type' => 'slot', 'status' => 1],
            ['code' => 'PP_LIVE_PRO', 'name' => 'Pragmatic Play Live', 'type' => 'live', 'status' => 1],
            ['code' => 'PGSOFT', 'name' => 'PGSoft', 'type' => 'slot', 'status' => 1],
            ['code' => 'HABANERO', 'name' => 'Habanero', 'type' => 'slot', 'status' => 1],
            ['code' => 'BOOONGO', 'name' => 'Booongo', 'type' => 'slot', 'status' => 1],
            ['code' => 'PLAYSON', 'name' => 'Playson', 'type' => 'slot', 'status' => 1],
            ['code' => 'CQ9', 'name' => 'CQ9', 'type' => 'slot', 'status' => 1],
            ['code' => 'EVOPLAY', 'name' => 'Evoplay', 'type' => 'slot', 'status' => 1],
            ['code' => 'TOPTREND', 'name' => 'TopTrend', 'type' => 'slot', 'status' => 1],
            ['code' => 'DREAMTECH', 'name' => 'DreamTech', 'type' => 'slot', 'status' => 1],
            ['code' => 'SPRIBE', 'name' => 'Spribe', 'type' => 'MN', 'status' => 1],
            ['code' => 'HACKSAW', 'name' => 'Hacksaw', 'type' => 'slot', 'status' => 1],
            ['code' => 'EVOLUTION', 'name' => 'Evolution Live', 'type' => 'live', 'status' => 1],
            ['code' => 'SPORTSBOOK', 'name' => 'Nexustrike', 'type' => 'SB', 'status' => 1],
            ['code' => 'FASTSPIN', 'name' => 'FastSpin', 'type' => 'slot', 'status' => 1],
            ['code' => 'FISHHUNTER', 'name' => 'Fish Hunter', 'type' => 'FT', 'status' => 1],
        ];
    }

    /**
     * 2. Fetch Game List for a specific provider (supports game_list & game_list_v2)
     */
    public function fetchGameList(string $providerCode): array
    {
        $v2Providers = ['PRAGMATIC', 'PGSOFT', 'REELKINGDOM', 'FATPANDA', 'HABANERO', 'CQ9'];
        $method = in_array(strtoupper($providerCode), $v2Providers) ? 'game_list_v2' : 'game_list';

        $response = $this->post([
            'method' => $method,
            'provider_code' => strtoupper($providerCode),
        ]);

        if ($response) {
            if (isset($response['games']) && is_array($response['games'])) {
                return $response['games'];
            }
            if (isset($response['data']) && is_array($response['data'])) {
                return isset($response['data']['games']) && is_array($response['data']['games']) ? $response['data']['games'] : $response['data'];
            }
            if (isset($response['game_list']) && is_array($response['game_list'])) {
                return $response['game_list'];
            }
            if (isset($response['list']) && is_array($response['list'])) {
                return $response['list'];
            }
        }

        // Try v1 fallback if v2 returned empty
        if ($method === 'game_list_v2') {
            $fallbackResp = $this->post([
                'method' => 'game_list',
                'provider_code' => strtoupper($providerCode),
            ]);
            if ($fallbackResp) {
                if (isset($fallbackResp['games']) && is_array($fallbackResp['games'])) {
                    return $fallbackResp['games'];
                }
                if (isset($fallbackResp['data']) && is_array($fallbackResp['data'])) {
                    return $fallbackResp['data'];
                }
            }
        }

        return [];
    }

    /**
     * 3. Multilingual game name parser
     */
    public function parseGameName(mixed $rawName, string $gameCode = ''): string
    {
        if (is_array($rawName)) {
            return $rawName['en'] ?? $rawName['hi'] ?? $rawName['vi'] ?? reset($rawName) ?? ($gameCode ?: 'Untitled Game');
        }

        if (is_string($rawName)) {
            $decoded = json_decode($rawName, true);
            if (is_array($decoded)) {
                return $decoded['en'] ?? reset($decoded) ?? ($gameCode ?: 'Untitled Game');
            }

            return trim($rawName) ?: ($gameCode ?: 'Untitled Game');
        }

        return $gameCode ?: 'Untitled Game';
    }

    /**
     * 4. Smart CDN Banner Mapper (No placeholder stock photos)
     */
    public function constructCdnBannerUrl(string $providerCode, string $gameCode, ?string $originalBanner = null): string
    {
        if (! empty($originalBanner)) {
            $originalBanner = trim($originalBanner);
            if (filter_var($originalBanner, FILTER_VALIDATE_URL)) {
                return $originalBanner;
            }
            if (str_starts_with($originalBanner, '//')) {
                return 'https:'.$originalBanner;
            }
            if (str_starts_with($originalBanner, '/')) {
                return rtrim($this->baseUrl, '/').$originalBanner;
            }

            return rtrim($this->baseUrl, '/').'/'.$originalBanner;
        }

        $provider = strtoupper(trim($providerCode));
        $code = trim($gameCode);

        switch ($provider) {
            case 'PRAGMATIC':
            case 'PRAGMATICPLAY':
            case 'REELKINGDOM':
            case 'PP_LIVE_PRO':
                return "https://assets.bd34fgabh.com/apps/game-assets/{$code}/{$code}_800x600_NB.avif";

            case 'PGSOFT':
                return "https://assets.bd34fgabh.com/img/pgsoft/{$code}.jpg";

            case 'HACKSAW':
                return "https://www-live.hacksawgaming.com/casino_thumbnails/{$code}.jpg";

            case 'SPRIBE':
                return "https://spribe.co/assets/games/{$code}/thumbnail.png";

            default:
                return "https://assets.bd34fgabh.com/apps/game-assets/{$code}/{$code}_800x600_NB.avif";
        }
    }

    /**
     * 5. Smart Category classifier
     */
    public function categorizeGame(?string $gameType, string $gameName, string $providerCode): string
    {
        $text = strtolower($gameType.' '.$gameName.' '.$providerCode);

        if (Str::contains($text, ['pp_live_pro', 'evolution', 'baccarat', 'blackjack', 'roulette', 'monopoly', 'crazy time', 'dealer', 'live'])) {
            return 'live';
        }

        if (Str::contains($text, ['aviator', 'crash', 'plinko', 'mines', 'dice', 'hilo', 'rocket', 'limbo', 'spribe', 'jetx', 'mn'])) {
            return 'crash';
        }

        if (Str::contains($text, ['fish', 'fishing', 'hunter', 'catch', 'ocean', 'deep', 'aquarium', 'ft', 'fishhunter'])) {
            return 'fishing';
        }

        if (Str::contains($text, ['football', 'fifa', 'counter-strike', 'csgo', 'dota', 'league', 'stadium', 'cup', 'tennis', 'basketball', 'sportsbook', 'sb'])) {
            return 'sports';
        }

        return 'slots';
    }

    /**
     * 6. Game Launch (method: game_launch)
     */
    public function launchGame(User $user, string $gameCode, string $providerCode, string $lang = 'en', ?int $rtp = null): array
    {
        $payload = [
            'method' => 'game_launch',
            'user_code' => $user->user_code,
            'provider_code' => strtoupper($providerCode),
            'game_code' => $gameCode,
            'lang' => $lang,
            'lobby_url' => $this->lobbyUrl,
        ];

        if ($rtp !== null) {
            $payload['rtp'] = $rtp;
        }

        if (! $this->mockMode) {
            $response = $this->post($payload);
            if ($response && isset($response['launch_url']) && ! empty($response['launch_url'])) {
                return [
                    'status' => 1,
                    'launch_url' => $response['launch_url'],
                    'message' => 'Game launched successfully',
                ];
            }
        }

        // Mock / Interactive Demo frame fallback for testing before IP whitelist
        $demoUrl = route('game.mock-frame', [
            'provider' => strtolower($providerCode),
            'game' => $gameCode,
            'user' => $user->user_code,
        ]);

        return [
            'status' => 1,
            'launch_url' => $demoUrl,
            'is_mock' => true,
            'message' => 'Launched in interactive simulation mode',
        ];
    }

    /**
     * 7. Full Synchronization Routine
     */
    public function syncGames(?string $targetProvider = null): array
    {
        $providers = $this->fetchProviders();
        $stats = [
            'providers_synced' => 0,
            'games_created' => 0,
            'games_updated' => 0,
            'errors' => 0,
        ];

        foreach ($providers as $pData) {
            $code = is_array($pData) ? ($pData['code'] ?? $pData['provider_code'] ?? '') : (string) $pData;
            $name = is_array($pData) ? ($pData['name'] ?? $pData['provider_name'] ?? $code) : (string) $code;

            if (empty($code)) {
                continue;
            }

            $code = strtoupper(trim($code));

            if ($targetProvider && strtoupper($targetProvider) !== $code) {
                continue;
            }

            Provider::updateOrCreate(
                ['code' => $code],
                ['name' => $name, 'status' => true]
            );
            $stats['providers_synced']++;

            $games = $this->fetchGameList($code);
            foreach ($games as $gData) {
                try {
                    $gameCode = $gData['game_code'] ?? $gData['code'] ?? '';
                    if (empty($gameCode)) {
                        continue;
                    }

                    $rawName = $gData['game_name'] ?? $gData['name'] ?? $gameCode;
                    $gameName = $this->parseGameName($rawName, $gameCode);
                    $gameType = $gData['game_type'] ?? $gData['type'] ?? 'slot';
                    $rawBanner = $gData['banner']
                        ?? $gData['image']
                        ?? $gData['img']
                        ?? $gData['banner_url']
                        ?? $gData['icon']
                        ?? $gData['thumbnail']
                        ?? $gData['game_banner']
                        ?? $gData['cover']
                        ?? $gData['url_thumb']
                        ?? null;
                    $bannerUrl = $this->constructCdnBannerUrl($code, $gameCode, $rawBanner);
                    $category = $this->categorizeGame($gameType, $gameName, $code);
                    $slug = Str::slug($gameName.'-'.$gameCode);

                    $game = Game::updateOrCreate(
                        [
                            'provider_code' => $code,
                            'game_code' => $gameCode,
                        ],
                        [
                            'name' => $gameName,
                            'slug' => $slug,
                            'banner_url' => $bannerUrl,
                            'category' => $category,
                            'rtp' => $gData['rtp'] ?? 96.50,
                            'is_popular' => (bool) ($gData['is_popular'] ?? false),
                            'is_featured' => (bool) ($gData['is_featured'] ?? false),
                            'status' => true,
                        ]
                    );

                    if ($game->wasRecentlyCreated) {
                        $stats['games_created']++;
                    } else {
                        $stats['games_updated']++;
                    }
                } catch (\Exception $e) {
                    $stats['errors']++;
                    Log::error("Failed to sync game {$code}/{$gameCode}: ".$e->getMessage());
                }
            }
        }

        return $stats;
    }
}
