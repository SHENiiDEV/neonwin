<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

class NexusGgrService
{
    public function request(string $method, array $payload = []): array
    {
        $agent = (string) config('nexus.agent_code');
        $token = (string) config('nexus.agent_token');
        if ($agent === '' || $token === '') {
            return ['status' => 0, 'code' => 0, 'msg' => 'Nexus credentials are not configured.', 'unknown' => false];
        }
        $interval = match ($method) {
            'provider_list', 'game_list', 'game_list_v2' => 1,
            'get_game_log' => 60,
            'agent_statistics' => 5,
            default => 0,
        };
        $limitMethod = $method === 'game_list_v2' ? 'game_list' : $method;
        if ($interval && ! Cache::add('nexus:limit:'.hash('sha256', $agent).':'.$limitMethod, true, $interval)) {
            return ['status' => 0, 'code' => 7770429, 'msg' => 'Rate limit reached. Try again later.', 'unknown' => false];
        }
        try {
            $response = Http::withOptions(config('nexus.force_ipv4') ? ['force_ip_resolve' => 'v4'] : [])->asJson()->acceptJson()->connectTimeout(3)->timeout(12)->post(config('nexus.base_url'), array_merge($payload, [
                'method' => $method, 'agent_code' => $agent, 'agent_token' => $token,
            ]));
            $result = $response->json();
            if ($response->status() === 403 && is_array($result) && ($result['code'] ?? null) === 'INVALID_IP') {
                return ['status' => 0, 'code' => 0, 'msg' => 'This server IP is not whitelisted in Nexus. Add it in the API panel.', 'unknown' => false];
            }
            if (! $response->successful() || ! is_array($result) || ! isset($result['status'], $result['code'])) {
                return ['status' => 0, 'code' => 0, 'msg' => 'Nexus response could not be confirmed. Reconciliation required.', 'unknown' => true];
            }
            $result = array_intersect_key($result, array_flip(['status', 'code', 'msg', 'providers', 'games', 'launch_url', 'token', 'fc_code', 'agent', 'user', 'users', 'agent_balance', 'history_url', 'slot', 'live', 'SB', 'total_count', 'page', 'perPage', 'bonus_id', 'tour_id', 'user_code', 'provider_code', 'game_code', 'max_bet', 'amount', 'bet_level', 'bet', 'total_bet', 'real_win', 'spin_count', 'expiration_time', 'rest_count', 'total_win', 'canceled_money']));
            if ($method !== 'game_launch') {
                unset($result['token'], $result['fc_code'], $result['launch_url']);
            }
            $result['msg'] = Str::limit(str_replace(array_filter([$agent, $token, (string) config('nexus.agent_secret')]), '[redacted]', (string) ($result['msg'] ?? 'Nexus request failed.')), 300);
            $result['unknown'] = in_array((int) $result['code'], [7770500, 7770502, 7770024, 7770026], true);

            return $result;
        } catch (ConnectionException $exception) {
            return ['status' => 0, 'code' => 0, 'msg' => 'Nexus did not respond. Reconciliation required.', 'unknown' => true];
        }
    }

    public static function successful(array $result): bool
    {
        return (int) ($result['status'] ?? 0) === 1 && (int) ($result['code'] ?? 0) === 7777777;
    }

    public function fetchProviders(): array
    {
        $result = $this->request('provider_list');

        return self::successful($result) ? ($result['providers'] ?? []) : [];
    }

    public function fetchGameList(string $providerCode): array
    {
        $method = in_array($providerCode, ['PRAGMATIC', 'PGSOFT', 'REELKINGDOM', 'FATPANDA', 'HABANERO', 'CQ9'], true) ? 'game_list_v2' : 'game_list';
        $result = $this->request($method, ['provider_code' => $providerCode]);

        return self::successful($result) ? ($result['games'] ?? []) : [];
    }

    public function parseGameName(mixed $rawName, string $gameCode = ''): string
    {
        if (is_string($rawName)) {
            $decoded = json_decode($rawName, true);
            $rawName = is_array($decoded) ? $decoded : $rawName;
        }
        if (is_array($rawName)) {
            $names = array_filter($rawName, fn ($name) => is_string($name) && trim($name) !== '');
            $rawName = $names['en'] ?? (reset($names) ?: $gameCode);
        }

        return is_string($rawName) && trim($rawName) !== '' ? trim($rawName) : $gameCode;
    }

    public function constructCdnBannerUrl(string $providerCode, string $gameCode, ?string $originalBanner = null): string
    {
        return $originalBanner && filter_var($originalBanner, FILTER_VALIDATE_URL) && in_array(parse_url($originalBanner, PHP_URL_SCHEME), ['https', 'http'], true) ? $originalBanner : '';
    }

    public function categorizeGame(?string $gameType, string $gameName, string $providerCode): string
    {
        return match ($gameType) {
            'live' => 'live', 'SB' => 'sports', 'MN' => 'crash', 'FH' => 'fishing', default => 'slots',
        };
    }

    public function launchGame(User $user, string $gameCode, string $providerCode, string $lang = 'en', ?int $rtp = null): array
    {
        if (! config('nexus.eur_agent_confirmed')) {
            return ['status' => 0, 'message' => 'Games are unavailable while the EUR wallet connection is being configured.'];
        }
        if (preg_match('/\s/', $user->user_code) || ! $user->user_code) {
            return ['status' => 0, 'message' => 'Your player code needs support before launch.'];
        }
        $payload = ['user_code' => $user->user_code, 'provider_code' => $providerCode, 'game_code' => $gameCode, 'lang' => 'en'];
        if (config('nexus.lobby_url')) {
            $payload['lobby_url'] = config('nexus.lobby_url');
        }
        $result = $this->request('game_launch', $payload);
        $url = $result['launch_url'] ?? '';
        if (! self::successful($result) || ! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https' || empty($result['token'])) {
            return ['status' => 0, 'message' => $result['msg'] ?? 'Game launch could not be confirmed. Please try again later.'];
        }
        GameSession::create([
            'user_id' => $user->id, 'provider_code' => $providerCode, 'game_code' => $gameCode,
            'token' => $result['token'], 'token_hash' => hash('sha256', $result['token']), 'fc_code' => $result['fc_code'] ?? null,
        ]);

        return ['status' => 1, 'launch_url' => $url, 'message' => 'Game launched.'];
    }

    public function getMoneyInfo(?string $userCode = null, bool $allUsers = false): array
    {
        return $this->request('money_info', $allUsers ? ['all_users' => true] : ($userCode ? ['user_code' => $userCode] : []));
    }

    public function getGameLog(?string $userCode = null, string $gameType = 'slot', ?string $start = null, ?string $end = null, int $page = 0, int $perPage = 1000): array
    {
        if (! in_array($gameType, ['slot', 'live', 'SB'], true) || ! $start || ! $end || $page < 0 || $perPage < 1 || $perPage > 1000000) {
            return ['status' => 0, 'code' => 7770400, 'msg' => 'Select a valid type, date range and page.'];
        }

        return $this->request('get_game_log', array_filter(['user_code' => $userCode, 'game_type' => $gameType, 'start' => $start, 'end' => $end, 'page' => $page, 'perPage' => $perPage], fn ($value) => $value !== null));
    }

    public function getGameHistory(string $userCode, string $gameCode, string $providerCode = 'PRAGMATIC'): array
    {
        if ($providerCode !== 'PRAGMATIC') {
            return ['status' => 0, 'code' => 7770407, 'msg' => 'Game history is available for PRAGMATIC only.'];
        }

        return $this->request('get_game_history', ['user_code' => $userCode, 'provider_code' => $providerCode, 'game_code' => $gameCode]);
    }

    public function syncGames(?string $targetProvider = null, ?callable $logger = null): array
    {
        $result = $this->request('provider_list');
        $stats = ['providers_synced' => 0, 'games_created' => 0, 'games_updated' => 0, 'errors' => 0];
        if (! self::successful($result)) {
            $stats['errors']++;
            if ($logger) {
                $logger($result['msg'], 'error');
            }

            return $stats;
        }
        $first = true;
        foreach ($result['providers'] ?? [] as $item) {
            $code = $item['code'];
            Provider::updateOrCreate(['code' => $code], ['name' => $item['name'], 'type' => $item['type'], 'status' => (int) $item['status'] === 1, 'synced_at' => now()]);
            $stats['providers_synced']++;
            if ($targetProvider && $targetProvider !== $code) {
                continue;
            }
            if (! $first) {
                Sleep::for(1)->seconds();
            }
            $first = false;
            $method = in_array($code, ['PRAGMATIC', 'PGSOFT', 'REELKINGDOM', 'FATPANDA', 'HABANERO', 'CQ9'], true) ? 'game_list_v2' : 'game_list';
            $games = $this->request($method, ['provider_code' => $code]);
            if (! self::successful($games)) {
                $stats['errors']++;

                continue;
            }
            foreach ($games['games'] ?? [] as $data) {
                $gameCode = $data['game_code'];
                $name = $this->parseGameName($data['game_name'], $gameCode);
                $game = Game::updateOrCreate(['provider_code' => $code, 'game_code' => $gameCode], [
                    'name' => $name, 'slug' => Str::slug($name.'-'.$code.'-'.$gameCode),
                    'banner_url' => $this->constructCdnBannerUrl($code, $gameCode, $data['banner'] ?? null),
                    'category' => $this->categorizeGame($item['type'], $name, $code),
                    'rtp' => $data['rtp'] ?? null, 'status' => (int) ($data['status'] ?? 0) === 1,
                    'bet_levels' => $code === 'PRAGMATIC' ? ($data['bet_levels'] ?? null) : null,
                ]);
                $stats[$game->wasRecentlyCreated ? 'games_created' : 'games_updated']++;
            }
            if (isset($games['games']) && is_array($games['games'])) {
                Game::where('provider_code', $code)->whereNotIn('game_code', array_column($games['games'], 'game_code'))->update(['status' => false]);
            }
        }

        return $stats;
    }
}
