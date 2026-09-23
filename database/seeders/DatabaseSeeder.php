<?php

namespace Database\Seeders;

use App\Models\ChatMessage;
use App\Models\Game;
use App\Models\LiveCommunityWin;
use App\Models\Provider;
use App\Models\Tournament;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Seed Providers
        $providersData = [
            ['code' => 'PRAGMATIC', 'name' => 'Pragmatic Play', 'logo_url' => 'https://assets.bd34fgabh.com/img/pragmatic.png'],
            ['code' => 'HACKSAW', 'name' => 'Hacksaw Gaming', 'logo_url' => 'https://assets.bd34fgabh.com/img/hacksaw.png'],
            ['code' => 'PGSOFT', 'name' => 'PG Soft', 'logo_url' => 'https://assets.bd34fgabh.com/img/pgsoft.png'],
            ['code' => 'NOLIMIT', 'name' => 'NoLimit City', 'logo_url' => 'https://assets.bd34fgabh.com/img/nolimit.png'],
            ['code' => 'NETENT', 'name' => 'NetEnt', 'logo_url' => 'https://assets.bd34fgabh.com/img/netent.png'],
            ['code' => 'SPRIBE', 'name' => 'Spribe', 'logo_url' => 'https://assets.bd34fgabh.com/img/spribe.png'],
            ['code' => 'EVOLUTION', 'name' => 'Evolution', 'logo_url' => 'https://assets.bd34fgabh.com/img/evolution.png'],
        ];

        foreach ($providersData as $p) {
            Provider::updateOrCreate(['code' => $p['code']], $p);
        }

        // 2. Seed Comprehensive Games with Official Provider CDN Posters
        $gamesData = [
            // Popular Pragmatic Slots
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20olympgate',
                'name' => 'Gates of Olympus',
                'slug' => 'gates-of-olympus',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20olympgate/vs20olympgate_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.50,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20sweetbonz',
                'name' => 'Sweet Bonanza',
                'slug' => 'sweet-bonanza',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20sweetbonz/vs20sweetbonz_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.48,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs40buffking',
                'name' => 'Buffalo King',
                'slug' => 'buffalo-king',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs40buffking/vs40buffking_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.06,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20sugarush',
                'name' => 'Sugar Rush',
                'slug' => 'sugar-rush',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20sugarush/vs20sugarush_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.50,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20doghouse',
                'name' => 'The Dog House Megaways',
                'slug' => 'the-dog-house-megaways',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20doghouse/vs20doghouse_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.55,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20bigbass',
                'name' => 'Big Bass Splash',
                'slug' => 'big-bass-splash',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20bigbass/vs20bigbass_800x600_NB.avif',
                'category' => 'fishing',
                'rtp' => 96.71,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20starlight',
                'name' => 'Starlight Princess',
                'slug' => 'starlight-princess',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20starlight/vs20starlight_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.50,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20wolfgold',
                'name' => 'Wolf Gold',
                'slug' => 'wolf-gold',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20wolfgold/vs20wolfgold_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.01,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20fruitsw',
                'name' => 'Fruit Party',
                'slug' => 'fruit-party',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20fruitsw/vs20fruitsw_800x600_NB.avif',
                'category' => 'slots',
                'rtp' => 96.47,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // PG Soft Slots
            [
                'provider_code' => 'PGSOFT',
                'game_code' => 'fortune-tiger',
                'name' => 'Fortune Tiger',
                'slug' => 'fortune-tiger',
                'banner_url' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-tiger.jpg',
                'category' => 'slots',
                'rtp' => 96.81,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PGSOFT',
                'game_code' => 'fortune-ox',
                'name' => 'Fortune Ox',
                'slug' => 'fortune-ox',
                'banner_url' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-ox.jpg',
                'category' => 'slots',
                'rtp' => 96.75,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PGSOFT',
                'game_code' => 'fortune-rabbit',
                'name' => 'Fortune Rabbit',
                'slug' => 'fortune-rabbit',
                'banner_url' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-rabbit.jpg',
                'category' => 'slots',
                'rtp' => 96.75,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PGSOFT',
                'game_code' => 'fortune-mouse',
                'name' => 'Fortune Mouse',
                'slug' => 'fortune-mouse',
                'banner_url' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-mouse.jpg',
                'category' => 'slots',
                'rtp' => 96.96,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // Hacksaw Slots
            [
                'provider_code' => 'HACKSAW',
                'game_code' => 'wanted-dead-wild',
                'name' => 'Wanted Dead or a Wild',
                'slug' => 'wanted-dead-or-a-wild',
                'banner_url' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1069.jpg',
                'category' => 'slots',
                'rtp' => 96.38,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'HACKSAW',
                'game_code' => 'dork-unit',
                'name' => 'Dork Unit',
                'slug' => 'dork-unit',
                'banner_url' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1172.jpg',
                'category' => 'slots',
                'rtp' => 96.24,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'HACKSAW',
                'game_code' => 'le-bandit',
                'name' => 'Le Bandit',
                'slug' => 'le-bandit',
                'banner_url' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1309.jpg',
                'category' => 'slots',
                'rtp' => 96.34,
                'is_popular' => true,
                'is_featured' => true,
            ],
            // Crash & Fast Games (Spribe)
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'aviator',
                'name' => 'Aviator',
                'slug' => 'aviator',
                'banner_url' => 'https://spribe.co/assets/games/aviator/thumbnail.png',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'mines',
                'name' => 'Mines',
                'slug' => 'mines',
                'banner_url' => 'https://spribe.co/assets/games/mines/thumbnail.png',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'plinko',
                'name' => 'Plinko',
                'slug' => 'plinko',
                'banner_url' => 'https://spribe.co/assets/games/plinko/thumbnail.png',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // Live Casino Games
            [
                'provider_code' => 'PP_LIVE_PRO',
                'game_code' => '101',
                'name' => 'VIP Blackjack Neon',
                'slug' => 'vip-blackjack-neon',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/101/101_800x600_NB.avif',
                'category' => 'live',
                'rtp' => 99.28,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PP_LIVE_PRO',
                'game_code' => '201',
                'name' => 'Mega Roulette Live',
                'slug' => 'mega-roulette-live',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/201/201_800x600_NB.avif',
                'category' => 'live',
                'rtp' => 97.30,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'PP_LIVE_PRO',
                'game_code' => '204',
                'name' => 'Speed Baccarat',
                'slug' => 'speed-baccarat',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/204/204_800x600_NB.avif',
                'category' => 'live',
                'rtp' => 98.94,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PP_LIVE_PRO',
                'game_code' => '501',
                'name' => 'Sweet Bonanza CandyLand Live',
                'slug' => 'sweet-bonanza-candyland',
                'banner_url' => 'https://assets.bd34fgabh.com/apps/game-assets/501/501_800x600_NB.avif',
                'category' => 'live',
                'rtp' => 96.48,
                'is_popular' => true,
                'is_featured' => true,
            ],
        ];

        foreach ($gamesData as $g) {
            Game::updateOrCreate(
                ['slug' => $g['slug']],
                $g
            );
        }

        // 3. Seed Live Community Wins (Matching mockup table)
        $wins = [
            ['user_name' => 'WildPapa442', 'game_name' => 'Gates of Olympus', 'bet_amount' => 10.00, 'multiplier' => 50.0, 'win_amount' => 500.00, 'game_image' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20olympgate/vs20olympgate_800x600_NB.avif'],
            ['user_name' => 'RichPiano', 'game_name' => 'Sweet Bonanza', 'bet_amount' => 2.00, 'multiplier' => 120.0, 'win_amount' => 240.00, 'game_image' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20sweetbonz/vs20sweetbonz_800x600_NB.avif'],
            ['user_name' => 'Arendsa', 'game_name' => 'Big Bass Splash', 'bet_amount' => 5.00, 'multiplier' => 10.0, 'win_amount' => 50.00, 'game_image' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20bigbass/vs20bigbass_800x600_NB.avif'],
            ['user_name' => 'Killer729', 'game_name' => 'Fortune Tiger', 'bet_amount' => 20.00, 'multiplier' => 25.0, 'win_amount' => 500.00, 'game_image' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-tiger.jpg'],
            ['user_name' => 'Lillesss', 'game_name' => 'Wanted Dead or a Wild', 'bet_amount' => 4.00, 'multiplier' => 320.0, 'win_amount' => 1280.00, 'game_image' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1069.jpg'],
            ['user_name' => 'CyberDragon', 'game_name' => 'Aviator', 'bet_amount' => 50.00, 'multiplier' => 14.5, 'win_amount' => 725.00, 'game_image' => 'https://spribe.co/assets/games/aviator/thumbnail.png'],
        ];

        foreach ($wins as $win) {
            LiveCommunityWin::create($win);
        }

        // 4. Seed Tournaments
        Tournament::updateOrCreate(
            ['title' => 'GRAND TOURNAMENT CYBER CRUSH'],
            [
                'prize_pool' => 800.00,
                'freespins' => 350,
                'starts_at' => Carbon::now()->subDays(2),
                'ends_at' => Carbon::now()->addDays(16)->addHours(21)->addMinutes(31)->addSeconds(19),
                'status' => 'active',
                'image_url' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20olympgate/vs20olympgate_800x600_NB.avif',
            ]
        );

        // 6. Seed Initial Chat Messages
        $messages = [
            ['user_name' => 'WildPapa442', 'vip_level' => 3, 'message' => 'Hey friends! Welcome to Neonwin! Good luck everyone today! 🚀', 'room' => 'global'],
            ['user_name' => 'RichPiano', 'vip_level' => 4, 'message' => 'Just hit 120x on Sweet Bonanza! 🍬🔥', 'room' => 'global'],
            ['user_name' => 'Arendsa', 'vip_level' => 2, 'message' => 'Nice hit mate! Good luck on the tournament leaderboard!', 'room' => 'global'],
            ['user_name' => 'Killer729', 'vip_level' => 5, 'message' => 'Presale tokens looking solid ⚡ Let\'s go!', 'room' => 'global'],
        ];

        foreach ($messages as $msg) {
            ChatMessage::create($msg);
        }
    }
}
