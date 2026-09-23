<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'surname',
        'email',
        'phone',
        'date_of_birth',
        'street',
        'city',
        'country',
        'postcode',
        'terms_accepted',
        'password',
        'user_code',
        'referral_code',
        'referred_by_id',
        'game_balance',
        'vault_balance',
        'vault_pin',
        'last_daily_bonus_at',
        'vip_xp',
        'vip_level',
        'currency',
        'avatar',
        'sound_enabled',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'vault_pin',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'date_of_birth' => 'date',
            'terms_accepted' => 'boolean',
            'password' => 'hashed',
            'game_balance' => 'decimal:2',
            'vault_balance' => 'decimal:2',
            'last_daily_bonus_at' => 'datetime',
            'vip_xp' => 'integer',
            'vip_level' => 'integer',
            'sound_enabled' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    /**
     * Check if user can claim their daily 1 SC bonus (once per 24 hours)
     */
    public function canClaimDailyBonus(): bool
    {
        if (! $this->last_daily_bonus_at) {
            return true;
        }

        return $this->last_daily_bonus_at->addHours(24)->isPast();
    }

    /**
     * Get the timestamp when the next daily bonus is available
     */
    public function nextDailyBonusAt(): ?Carbon
    {
        if (! $this->last_daily_bonus_at) {
            return null;
        }

        return $this->last_daily_bonus_at->copy()->addHours(24);
    }

    protected static function booted(): void
    {
        static::creating(function ($user) {
            if (empty($user->user_code)) {
                $user->user_code = 'NW-'.strtoupper(Str::random(8));
            }
            if (empty($user->referral_code)) {
                $user->referral_code = 'NW-REF-'.strtoupper(Str::random(6));
            }
            if (empty($user->avatar)) {
                $user->avatar = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150&auto=format&fit=crop&q=80';
            }
        });
    }

    public function transactions()
    {
        return $this->hasMany(GameTransaction::class);
    }

    public function crates()
    {
        return $this->hasMany(UserCrate::class);
    }

    public function favorites()
    {
        return $this->hasMany(UserFavorite::class);
    }

    public function recentGames()
    {
        return $this->hasMany(UserRecentGame::class);
    }

    public function sentTips()
    {
        return $this->hasMany(TippingTransaction::class, 'sender_id');
    }

    public function receivedTips()
    {
        return $this->hasMany(TippingTransaction::class, 'recipient_id');
    }

    public function referrals()
    {
        return $this->hasMany(User::class, 'referred_by_id');
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referred_by_id');
    }

    public function referralEarnings()
    {
        return $this->hasMany(ReferralEarning::class, 'referrer_id');
    }

    /**
     * Award VIP XP points based on wager amount and trigger milestone crates
     */
    public function awardVipXp(float $wagerAmount): void
    {
        $xpEarned = (int) round($wagerAmount * 10);
        if ($xpEarned > 0) {
            $oldLevel = $this->vip_level ?: 1;
            $this->vip_xp += $xpEarned;

            // Calculate VIP level: Level 1: 0-5000, Level 2: 5000-20000, Level 3: 20000-50000, etc.
            $newLevel = 1;
            if ($this->vip_xp >= 100000) {
                $newLevel = 5;
            } elseif ($this->vip_xp >= 50000) {
                $newLevel = 4;
            } elseif ($this->vip_xp >= 20000) {
                $newLevel = 3;
            } elseif ($this->vip_xp >= 5000) {
                $newLevel = 2;
            }

            if ($newLevel > $oldLevel) {
                $this->vip_level = $newLevel;
                // Grant Level-up Cyber Crate
                $tierNames = [1 => 'Bronze Recruit', 2 => 'Silver Shadow', 3 => 'Gold Ronin', 4 => 'Platinum Cyber', 5 => 'Neon Overlord'];
                $tierSlug = [1 => 'bronze', 2 => 'silver', 3 => 'gold', 4 => 'platinum', 5 => 'mythic'];
                UserCrate::create([
                    'user_id' => $this->id,
                    'type' => 'vip_level_'.$newLevel,
                    'name' => ($tierNames[$newLevel] ?? 'VIP').' Rank Crate',
                    'tier' => $tierSlug[$newLevel] ?? 'gold',
                    'status' => 'pending',
                ]);
            }

            $this->save();
        }
    }
}
