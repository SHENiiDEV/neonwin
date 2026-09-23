<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CrateController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GamePreferenceController;
use App\Http\Controllers\GgrGoldApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SyndicateController;
use App\Http\Controllers\TippingController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\VipController;
use Illuminate\Support\Facades\Route;

// Main Home Page (Lobby)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Game Launch & Play
Route::get('/game/{slug}', [GameController::class, 'play'])->name('game.play');
Route::get('/mock-game-frame', [GameController::class, 'mockFrame'])->name('game.mock-frame');
Route::get('/api/games/search', [GameController::class, 'search'])->name('api.games.search');

// VIP Club & Gamification
Route::get('/vip', [VipController::class, 'index'])->name('vip');

// Player Profile & Statistics
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

// Legal & Information Pages
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/sweeps-rules', [LegalController::class, 'sweepsRules'])->name('legal.sweeps-rules');
Route::get('/responsible-gaming', [LegalController::class, 'responsibleGaming'])->name('legal.responsible-gaming');
Route::get('/about', [LegalController::class, 'about'])->name('legal.about');
Route::get('/faq', [LegalController::class, 'faq'])->name('legal.faq');
Route::get('/help', [LegalController::class, 'faq'])->name('legal.help');
Route::get('/contact', [LegalController::class, 'contact'])->name('legal.contact');
Route::post('/contact', [LegalController::class, 'submitContact'])->name('legal.contact.submit');

// Auth routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Wallet & Chat
Route::post('/deposit', [DepositController::class, 'deposit'])->name('deposit');
Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');

// Gamification: Cyber Crates
Route::get('/api/crates', [CrateController::class, 'index'])->name('crates.index');
Route::post('/api/crates/{id}/open', [CrateController::class, 'open'])->name('crates.open');

// Gamification: Cyber Vault (Piggy Bank)
Route::post('/vault/deposit', [VaultController::class, 'deposit'])->name('vault.deposit');
Route::post('/vault/withdraw', [VaultController::class, 'withdraw'])->name('vault.withdraw');
Route::post('/vault/set-pin', [VaultController::class, 'setPin'])->name('vault.set-pin');

// Gamification: Cyber Syndicate (Referral Program)
Route::get('/api/syndicate', [SyndicateController::class, 'index'])->name('syndicate.index');

// Gamification: Player-to-Player Tipping
Route::post('/tipping/send', [TippingController::class, 'tip'])->name('tipping.send');

// Gamification: Multiplier Hunts & Live Challenges
Route::get('/api/challenges', [ChallengeController::class, 'index'])->name('challenges.index');

// Personalization: Favorites, Recent Games & Sound
Route::post('/api/games/favorite', [GamePreferenceController::class, 'toggleFavorite'])->name('games.favorite');
Route::get('/api/games/favorites', [GamePreferenceController::class, 'getFavorites'])->name('games.favorites.list');
Route::post('/api/games/recent', [GamePreferenceController::class, 'logRecent'])->name('games.recent.log');
Route::post('/api/user/sound-toggle', [GamePreferenceController::class, 'toggleSound'])->name('user.sound.toggle');
Route::get('/api/user/balance', function () {
    $user = auth()->user();

    return response()->json([
        'balance' => $user ? (float) $user->game_balance : 0,
        'vault_balance' => $user ? (float) $user->vault_balance : 0,
    ]);
})->middleware('auth')->name('user.balance');

// Root gold_api Webhook endpoint
Route::post('/gold_api', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/api/ggr-gold/callback', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/api/ggr/callback', [GgrGoldApiController::class, 'handleCallback']);
