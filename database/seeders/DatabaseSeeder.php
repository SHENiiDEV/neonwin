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

        // 3. Seed Comprehensive Games with Official Provider CDN Posters
        $gamesData = [
            // Popular Pragmatic & PG Slots
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
                'game_code' => 'vs20alohaking',
                'name' => 'Aloha Queen',
                'slug' => 'aloha-queen',
                'banner_url' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80',
                'category' => 'slots',
                'rtp' => 96.30,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PRAGMATIC',
                'game_code' => 'vs20clovercharm',
                'name' => 'Clover Charm',
                'slug' => 'clover-charm',
                'banner_url' => 'https://images.unsplash.com/photo-1511193311914-0346f16efe90?w=600&auto=format&fit=crop&q=80',
                'category' => 'slots',
                'rtp' => 96.20,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'PGSOFT',
                'game_code' => 'fortune-genie',
                'name' => 'Fortune Genie',
                'slug' => 'fortune-genie',
                'banner_url' => 'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=600&auto=format&fit=crop&q=80',
                'category' => 'slots',
                'rtp' => 96.75,
                'is_popular' => true,
                'is_featured' => false,
            ],
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
                'game_code' => 'rip-city',
                'name' => 'RIP City',
                'slug' => 'rip-city',
                'banner_url' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1199.jpg',
                'category' => 'slots',
                'rtp' => 96.22,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // Crash & Fast Games
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'aviator',
                'name' => 'Aviator',
                'slug' => 'aviator',
                'banner_url' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&auto=format&fit=crop&q=80',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'mines',
                'name' => 'Mines Cyber',
                'slug' => 'mines-cyber',
                'banner_url' => 'https://images.unsplash.com/photo-1618005182384-a83a8bd57fbe?w=600&auto=format&fit=crop&q=80',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'SPRIBE',
                'game_code' => 'plinko',
                'name' => 'Plinko Neon',
                'slug' => 'plinko-neon',
                'banner_url' => 'https://images.unsplash.com/photo-1550745165-9bc0b252726f?w=600&auto=format&fit=crop&q=80',
                'category' => 'crash',
                'rtp' => 97.00,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // Live Casino Games
            [
                'provider_code' => 'EVOLUTION',
                'game_code' => 'live-blackjack-vip',
                'name' => 'VIP Blackjack Neon',
                'slug' => 'vip-blackjack-neon',
                'banner_url' => 'https://images.unsplash.com/photo-1511193311914-0346f16efe90?w=600&auto=format&fit=crop&q=80',
                'category' => 'live',
                'rtp' => 99.28,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'EVOLUTION',
                'game_code' => 'lightning-roulette',
                'name' => 'Lightning Roulette',
                'slug' => 'lightning-roulette',
                'banner_url' => 'https://images.unsplash.com/photo-1596838132731-3301c3fd4317?w=600&auto=format&fit=crop&q=80',
                'category' => 'live',
                'rtp' => 97.30,
                'is_popular' => true,
                'is_featured' => true,
            ],
            [
                'provider_code' => 'EVOLUTION',
                'game_code' => 'speed-baccarat-a',
                'name' => 'Speed Baccarat',
                'slug' => 'speed-baccarat',
                'banner_url' => 'https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=600&auto=format&fit=crop&q=80',
                'category' => 'live',
                'rtp' => 98.94,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'EVOLUTION',
                'game_code' => 'crazy-time',
                'name' => 'Crazy Time Deluxe',
                'slug' => 'crazy-time-deluxe',
                'banner_url' => 'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=600&auto=format&fit=crop&q=80',
                'category' => 'live',
                'rtp' => 96.08,
                'is_popular' => true,
                'is_featured' => false,
            ],
            // Sports / Esports
            [
                'provider_code' => 'NETENT',
                'game_code' => 'champions-cup',
                'name' => 'Football Champions Cup',
                'slug' => 'football-champions-cup',
                'banner_url' => 'https://images.unsplash.com/photo-1508098682722-e99c43a406b2?w=600&auto=format&fit=crop&q=80',
                'category' => 'sports',
                'rtp' => 96.82,
                'is_popular' => true,
                'is_featured' => false,
            ],
            [
                'provider_code' => 'NOLIMIT',
                'game_code' => 'cyber-esports-battle',
                'name' => 'Cyber Esports Arena',
                'slug' => 'cyber-esports-arena',
                'banner_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=600&auto=format&fit=crop&q=80',
                'category' => 'sports',
                'rtp' => 96.15,
                'is_popular' => true,
                'is_featured' => false,
            ],
        ];

        foreach ($gamesData as $g) {
            Game::updateOrCreate(
                ['provider_code' => $g['provider_code'], 'game_code' => $g['game_code']],
                $g
            );
        }

        // 4. Seed Live Community Wins (Matching mockup table)
        $wins = [
            ['user_name' => 'WildPapa442', 'game_name' => 'Gates of Olympus', 'bet_amount' => 10.00, 'multiplier' => 50.0, 'win_amount' => 500.00, 'game_image' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20olympgate/vs20olympgate_800x600_NB.avif'],
            ['user_name' => 'RichPiano', 'game_name' => 'Sweet Bonanza', 'bet_amount' => 2.00, 'multiplier' => 120.0, 'win_amount' => 240.00, 'game_image' => 'https://assets.bd34fgabh.com/apps/game-assets/vs20sweetbonz/vs20sweetbonz_800x600_NB.avif'],
            ['user_name' => 'Arendsa', 'game_name' => 'Starburst', 'bet_amount' => 5.00, 'multiplier' => 10.0, 'win_amount' => 50.00, 'game_image' => 'https://images.unsplash.com/photo-1606167668584-78701c57f13d?w=600&auto=format&fit=crop&q=80'],
            ['user_name' => 'Killer729', 'game_name' => 'Fortune Tiger', 'bet_amount' => 20.00, 'multiplier' => 25.0, 'win_amount' => 500.00, 'game_image' => 'https://assets.bd34fgabh.com/img/pgsoft/fortune-tiger.jpg'],
            ['user_name' => 'Lillesss', 'game_name' => 'Wanted Dead or a Wild', 'bet_amount' => 4.00, 'multiplier' => 320.0, 'win_amount' => 1280.00, 'game_image' => 'https://www-live.hacksawgaming.com/casino_thumbnails/1069.jpg'],
            ['user_name' => 'CyberDragon', 'game_name' => 'Aviator', 'bet_amount' => 50.00, 'multiplier' => 14.5, 'win_amount' => 725.00, 'game_image' => 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=600&auto=format&fit=crop&q=80'],
        ];

        foreach ($wins as $win) {
            LiveCommunityWin::create($win);
        }

        // 5. Seed Tournaments
        Tournament::updateOrCreate(
            ['title' => 'GRAND TOURNAMENT CYBER CRUSH'],
            [
                'prize_pool' => 800.00,
                'freespins' => 350,
                'starts_at' => Carbon::now()->subDays(2),
                'ends_at' => Carbon::now()->addDays(16)->addHours(21)->addMinutes(31)->addSeconds(19),
                'status' => 'active',
                'image_url' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?w=800&auto=format&fit=crop&q=80',
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
