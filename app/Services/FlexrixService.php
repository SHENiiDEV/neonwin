<?php

namespace App\Services;

use App\Models\Game;
use App\Models\GameSession;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class FlexrixService
{
    public function configured(string $channel): bool
    {
        return filled(config('gaming.flexrix.'.$channel.'_key')) && filled(config('gaming.flexrix.'.$channel.'_secret'));
    }

    /** RFC 3986 form signing, including recursively sorted bracket fields. */
    public static function canonical(array $parameters): string
    {
        ksort($parameters, SORT_STRING);
        $pairs = [];
        foreach ($parameters as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (is_array($value)) {
                $nested = [];
                foreach ($value as $child => $item) {
                    $nested[$key.'['.$child.']'] = $item;
                }
                $pairs[] = self::canonical($nested);
            } else {
                $pairs[] = $key.'='.rawurlencode(is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
            }
        }

        return implode('&', $pairs);
    }

    public function request(string $method, string $path, array $parameters = []): array
    {
        if (! $this->configured('casino')) {
            throw new RuntimeException('Flexrix Casino credentials are missing.');
        }
        $base = rtrim(config('gaming.flexrix.base_url'), '/');
        if (! self::httpsUrl($base)) {
            throw new RuntimeException('Flexrix API must use HTTPS.');
        }
        $headers = ['X-Merchant-Id' => config('gaming.flexrix.casino_key'), 'X-Timestamp' => (string) now()->timestamp, 'X-Nonce' => bin2hex(random_bytes(16))];
        $headers['X-Sign'] = hash_hmac('sha1', self::canonical(array_merge($parameters, $headers)), config('gaming.flexrix.casino_secret'));
        $client = Http::acceptJson()->withHeaders($headers)->connectTimeout(5)->timeout(20)->withoutRedirecting();
        if (config('gaming.flexrix.force_ipv4')) {
            $client = $client->withOptions(['force_ip_resolve' => 'v4']);
        }
        $response = $method === 'GET' ? $client->get($base.$path, $parameters) : $client->post($base.$path, $parameters);
        if (! $response->successful() || ! is_array($response->json())) {
            throw new RuntimeException('Flexrix request failed (HTTP '.$response->status().').');
        }

        return $response->json();
    }

    public static function httpsUrl(mixed $url): bool
    {
        return is_string($url) && filter_var($url, FILTER_VALIDATE_URL) && parse_url($url, PHP_URL_SCHEME) === 'https' && ! parse_url($url, PHP_URL_USER) && ! parse_url($url, PHP_URL_PASS);
    }

    public static function providerCode(string $remote): string
    {
        return strtoupper('FLEXRIX_'.substr(Str::slug($remote, '_'), 0, 60).'_'.substr(hash('sha256', $remote), 0, 8));
    }

    public function launch(User $user, Game $game, string $language): array
    {
        try {
            $result = $this->request('POST', '/v1/native/'.rawurlencode($game->external_code).'/launch', [
                'player_id' => $user->user_code, 'currency' => 'EUR', 'language' => preg_match('/^[a-z]{2}$/D', $language) ? $language : 'en',
                'balance' => $user->eur_balance_cents / 100, 'return_url' => route('home'),
            ]);
            if (! self::httpsUrl($result['url'] ?? null)) {
                throw new RuntimeException('Invalid launch URL.');
            }
            $this->session($user, $game->provider_code, $game->game_code, $result['url']);

            return ['launch_url' => $result['url']];
        } catch (Throwable) {
            return ['message' => 'Flexrix Casino is unavailable. Please try again later.'];
        }
    }

    public function sportsbook(User $user, string $language = 'en'): array
    {
        $url = config('gaming.flexrix.sportsbook_url');
        if (! $this->configured('sportsbook') || ! self::httpsUrl($url) || parse_url($url, PHP_URL_QUERY) || parse_url($url, PHP_URL_FRAGMENT)) {
            return ['message' => 'Sportsbook activation is pending. Please try again later.'];
        }
        $language = preg_match('/^[a-z]{2}$/D', $language) ? $language : 'en';
        $encode = fn (string $value): string => rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        $header = $encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_THROW_ON_ERROR));
        $payload = $encode(json_encode(['iss' => config('gaming.flexrix.sportsbook_key'), 'sub' => $user->user_code, 'username' => $user->name, 'currency' => 'EUR', 'language' => $language, 'iat' => now()->timestamp, 'exp' => now()->addMinutes(5)->timestamp, 'jti' => (string) Str::uuid()], JSON_THROW_ON_ERROR));
        $token = $header.'.'.$payload.'.'.$encode(hash_hmac('sha256', $header.'.'.$payload, config('gaming.flexrix.sportsbook_secret'), true));
        $this->session($user, 'FLEXRIX_SPORTSBOOK', '', $token);

        return ['launch_url' => rtrim($url, '/').'/?'.http_build_query(['token' => $token, 'lang' => $language], '', '&', PHP_QUERY_RFC3986)];
    }

    private function session(User $user, string $provider, string $game, string $token): void
    {
        GameSession::create(['user_id' => $user->id, 'provider_code' => $provider, 'game_code' => $game, 'token' => $token, 'token_hash' => hash('sha256', $token), 'status' => 'launched']);
    }

    /** Full catalogue is validated before any availability changes are committed. */
    public function sync(): array
    {
        try {
            $providers = $this->request('GET', '/v1/native/providers')['items'] ?? null;
            if (! is_array($providers) || ! array_is_list($providers)) {
                throw new RuntimeException('Invalid provider catalogue.');
            }
            $providersByName = [];
            foreach ($providers as $provider) {
                if (! is_string($provider['code'] ?? null) || ! is_string($provider['label'] ?? null)) {
                    throw new RuntimeException('Invalid provider.');
                }
                $providersByName[Str::lower(trim($provider['code']))] = $provider;
                $providersByName[Str::lower(trim($provider['label']))] = $provider;
            }
            $items = [];
            $expectedTotal = null;
            for ($page = 1; $page <= 1000; $page++) {
                $data = $this->request('GET', '/v1/native/games', ['page' => $page, 'per_page' => 200, 'include_inactive' => 1, 'image_style' => 'landscape']);
                $pages = filter_var($data['_meta']['pageCount'] ?? null, FILTER_VALIDATE_INT);
                $total = filter_var($data['_meta']['totalCount'] ?? null, FILTER_VALIDATE_INT);
                if (! is_array($data['items'] ?? null) || ! array_is_list($data['items']) || $pages === false || $pages < 1 || $pages > 1000 || $total === false || $total < 0 || ($expectedTotal !== null && $expectedTotal !== $total) || (int) ($data['_meta']['currentPage'] ?? 0) !== $page) {
                    throw new RuntimeException('Incomplete catalogue.');
                }
                $expectedTotal = $total;
                foreach ($data['items'] as $item) {
                    foreach (['uuid', 'slug', 'name', 'provider'] as $key) {
                        if (! is_string($item[$key] ?? null) || $item[$key] === '' || strlen($item[$key]) > 200) {
                            throw new RuntimeException('Invalid catalogue entry.');
                        }
                    }
                    if (isset($items[$item['uuid']])) {
                        throw new RuntimeException('Repeated catalogue page.');
                    }
                    $items[$item['uuid']] = $item;
                }
                if ($page >= $pages) {
                    break;
                }
            }
            if (count($items) !== $expectedTotal) {
                throw new RuntimeException('Catalogue count mismatch.');
            }

            return DB::transaction(function () use ($items, $providersByName): array {
                $codes = [];
                $syncedProviders = [];
                foreach ($items as $item) {
                    $code = self::providerCode($item['provider']);
                    $codes[] = $code;
                    if (! isset($syncedProviders[$code])) {
                        $provider = $providersByName[Str::lower(trim($item['provider']))] ?? null;
                        Provider::updateOrCreate(['code' => $code], [
                            'integration' => 'flexrix',
                            'external_code' => $provider['code'] ?? $item['provider'],
                            'name' => $provider['label'] ?? $item['provider'],
                            'status' => true,
                            'synced_at' => now(),
                        ]);
                        $syncedProviders[$code] = true;
                    }
                    Game::updateOrCreate(['provider_code' => $code, 'game_code' => $item['uuid']], [
                        'integration' => 'flexrix', 'external_code' => $item['slug'], 'name' => $item['name'], 'slug' => 'flexrix-'.Str::slug($item['slug']).'-'.substr(hash('sha256', $item['uuid']), 0, 8),
                        'banner_url' => self::httpsUrl($item['image_landscape'] ?? null) ? $item['image_landscape'] : (self::httpsUrl($item['image'] ?? null) ? $item['image'] : null),
                        'category' => match ($item['type'] ?? 'slots') {
                            'live', 'live_casino', 'live-casino', 'live casino' => 'live', 'crash' => 'crash', 'table', 'table_games' => 'table', default => 'slots'
                        },
                        'rtp' => isset($item['rtp']) && is_numeric($item['rtp']) && $item['rtp'] >= 0 && $item['rtp'] <= 100 ? $item['rtp'] : null,
                        'bet_levels' => $item['bet_options'] ?? [], 'has_freespins' => (bool) ($item['has_freespins'] ?? false), 'status' => (bool) ($item['active'] ?? true),
                    ]);
                }
                Game::where('integration', 'flexrix')->whereNotIn('game_code', array_keys($items))->update(['status' => false]);
                Provider::where('integration', 'flexrix')->whereNotIn('code', array_unique($codes))->update(['status' => false]);

                return ['errors' => 0, 'games' => count($items), 'providers' => count(array_unique($codes))];
            });
        } catch (Throwable) {
            return ['errors' => 1, 'games' => 0, 'providers' => 0];
        }
    }
}
