<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ChallengeController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\CrateController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\GamePreferenceController;
use App\Http\Controllers\GgrGoldApiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LaunchAdminController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\NexusReconciliationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\SyndicateController;
use App\Http\Controllers\TippingController;
use App\Http\Controllers\VaultController;
use App\Http\Controllers\VipAdminController;
use App\Http\Controllers\VipController;
use App\Http\Controllers\WheelController;
use Illuminate\Support\Facades\Route;

// Main Home Page (Lobby)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Game Launch & Play
Route::get('/game/{slug}', [GameController::class, 'play'])->name('game.play');

Route::get('/api/games/search', [GameController::class, 'search'])->name('api.games.search');

// VIP Club & Gamification
Route::get('/wheel', [WheelController::class, 'index'])->name('wheel');
Route::get('/vip', [VipController::class, 'index'])->name('vip');

// Player Profile & Statistics
Route::get('/profile', [ProfileController::class, 'index'])->name('profile');
Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

// Legal & Information Pages
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');
Route::get('/promotions', [LegalController::class, 'sweepsRules'])->name('legal.promotions');
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
Route::post('/deposit', [DepositController::class, 'deposit'])->middleware(['auth', 'throttle:10,1'])->name('deposit');
Route::post('/chat/send', [ChatController::class, 'sendMessage'])->name('chat.send');

// Gamification: Cyber Crates
Route::get('/api/crates', [CrateController::class, 'index'])->name('crates.index');
Route::post('/api/crates/{id}/open', [CrateController::class, 'open'])->middleware('auth')->name('crates.open');

// Gamification: Cyber Vault (Piggy Bank)
Route::post('/vault/deposit', [VaultController::class, 'deposit'])->middleware('auth')->name('vault.deposit');
Route::post('/vault/withdraw', [VaultController::class, 'withdraw'])->middleware('auth')->name('vault.withdraw');
Route::post('/vault/set-pin', [VaultController::class, 'setPin'])->middleware('auth')->name('vault.set-pin');

// Gamification: Cyber Syndicate (Referral Program)
Route::get('/api/syndicate', [SyndicateController::class, 'index'])->name('syndicate.index');

// Gamification: Player-to-Player Tipping
Route::post('/tipping/send', [TippingController::class, 'tip'])->middleware('auth')->name('tipping.send');

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
        'balance' => $user ? $user->eur_balance_cents / 100 : 0,
        'vault_balance' => $user ? $user->eur_vault_cents / 100 : 0,
    ]);
})->middleware('auth')->name('user.balance');

// Root gold_api Webhook endpoint
Route::post('/gold_api', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/api/ggr-gold/callback', [GgrGoldApiController::class, 'handleCallback']);
Route::post('/api/ggr/callback', [GgrGoldApiController::class, 'handleCallback']);

Route::middleware('auth')->group(function () {
    Route::post('/wheel/spin', [WheelController::class, 'spin'])->middleware('throttle:10,1')->name('wheel.spin');
    Route::post('/vip/check-in', [VipController::class, 'checkin'])->middleware('throttle:20,1')->name('vip.checkin');
    Route::post('/vip/rewards/{reward}/claim', [VipController::class, 'claim'])->middleware('throttle:30,1')->whereNumber('reward')->name('vip.claim');
    Route::get('/sportsbook', [GameController::class, 'sportsbook'])->name('sportsbook');
    Route::get('/verification', [KycController::class, 'index'])->name('verification');
    Route::post('/verification', [KycController::class, 'store'])->middleware('throttle:10,1')->name('verification.store');
    Route::get('/verification/{submission}/{kind}', [KycController::class, 'download'])->name('verification.file');
    Route::post('/withdraw', [KycController::class, 'withdrawal'])->name('withdraw');
    Route::prefix('admin')->name('admin.')->middleware('can:admin')->group(function () {
        Route::get('/launch', [LaunchAdminController::class, 'index'])->name('launch.index');
        Route::post('/launch/wheel', [LaunchAdminController::class, 'wheel'])->name('launch.wheel');
        Route::post('/launch/fund', [LaunchAdminController::class, 'fund'])->middleware('throttle:10,1')->name('launch.fund');
        Route::post('/launch/wallet', [LaunchAdminController::class, 'wallet'])->middleware('throttle:10,1')->name('launch.wallet');
        Route::post('/launch/rakeback', [LaunchAdminController::class, 'rakeback'])->name('launch.rakeback');
        Route::post('/launch/telegram', [LaunchAdminController::class, 'telegram'])->name('launch.telegram');
        Route::post('/launch/notices/{notice}/retry', [LaunchAdminController::class, 'retry'])->middleware('throttle:10,1')->name('launch.retry');
        Route::get('/vip', [VipAdminController::class, 'index'])->name('vip.index');
        Route::post('/vip/tiers', [VipAdminController::class, 'tiers'])->name('vip.tiers');
        Route::post('/vip/quests', [VipAdminController::class, 'quest'])->name('vip.quests.store');
        Route::post('/vip/quests/{quest}', [VipAdminController::class, 'quest'])->name('vip.quests.update');
        Route::get('/promotions', [PromotionController::class, 'index'])->name('promotions.index');
        Route::post('/promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::post('/promotions/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::post('/promotion-redemptions/{redemption}/issue', [PromotionController::class, 'issue'])->name('promotions.issue');
        Route::get('/', [AdminController::class, 'index'])->name('index');
        Route::post('/operations', [AdminController::class, 'operate'])->name('operations');
        Route::post('/operations/{operation}/reconcile', [NexusReconciliationController::class, 'update'])->name('reconcile');
        Route::post('/operations/{operation}/cancel', [AdminController::class, 'cancel'])->name('cancel');
        Route::post('/kyc/{submission}', [KycController::class, 'review'])->name('kyc.review');
        Route::post('/sync', [AdminController::class, 'sync'])->name('sync');
        Route::post('/agent-balance', [AdminController::class, 'balance'])->name('balance');
        Route::post('/game-log', [AdminController::class, 'gameLog'])->name('game-log');
        Route::post('/alerts/{alert}/resolve', [AdminController::class, 'resolve'])->name('alerts.resolve');
    });
});

Route::get('/login', fn () => redirect()->route('home', ['login' => 1]))->name('login.page');
Route::get('/cashier', fn () => redirect()->route('home', ['cashier' => 1]))->name('cashier');
