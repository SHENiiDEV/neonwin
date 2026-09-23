import React, { useState } from 'react';
import { usePage, Link } from '@inertiajs/react';
import { 
    X, 
    Crown, 
    Trophy, 
    Sparkles, 
    ShieldCheck, 
    Zap, 
    Gift, 
    TrendingUp, 
    ArrowRight, 
    Check, 
    Lock,
    Coins, 
    ChevronRight,
    Flame,
    Headphones,
    Award
} from 'lucide-react';

const vipTiers = [
    { level: 1, name: 'Bronze Recruit', minXp: 0, maxXp: 5000, cashback: '2%', rakeback: '5%', bonus: '25 SC', icon: '🥉', badgeColor: '#d97706' },
    { level: 2, name: 'Silver Shadow', minXp: 5000, maxXp: 20000, cashback: '5%', rakeback: '8%', bonus: '100 SC', icon: '🥈', badgeColor: '#94a3b8' },
    { level: 3, name: 'Gold Ronin', minXp: 20000, maxXp: 50000, cashback: '8%', rakeback: '12%', bonus: '300 SC', icon: '🥇', badgeColor: '#f59e0b' },
    { level: 4, name: 'Platinum Cyber', minXp: 50000, maxXp: 100000, cashback: '12%', rakeback: '15%', bonus: '1,000 SC', icon: '💎', badgeColor: '#06b6d4' },
    { level: 5, name: 'Neon Overlord', minXp: 100000, maxXp: 500000, cashback: '18%', rakeback: '20%', bonus: '5,000 SC', icon: '👑', badgeColor: '#ec4899' },
];

export default function VipModal({ isOpen, onClose }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [activeTab, setActiveTab] = useState('tiers'); // 'tiers' | 'stats' | 'perks'
    const userLevel = user?.vip_level || 2;
    const userXp = user?.vip_xp || 12500;
    const currentTier = vipTiers.find(t => t.level === userLevel) || vipTiers[1];
    const nextTier = vipTiers.find(t => t.level === Math.min(5, userLevel + 1));

    if (!isOpen) return null;

    const xpRange = currentTier.maxXp - currentTier.minXp;
    const xpEarnedInTier = Math.max(0, userXp - currentTier.minXp);
    const progressPercent = Math.min(100, Math.round((xpEarnedInTier / xpRange) * 100));

    return (
        <div className="nw-topup-backdrop" onClick={e => { if (e.target === e.currentTarget) onClose(); }}>
            <section 
                role="dialog" 
                aria-modal="true" 
                aria-labelledby="vip-title" 
                aria-describedby="vip-description" 
                className="nw-topup"
            >
                {/* Close Button */}
                <button 
                    onClick={onClose} 
                    aria-label="Close VIP Club dialog" 
                    className="nw-topup-close"
                >
                    <X size={18} />
                </button>

                {/* Left Story Sidebar (Top-up signature style) */}
                <aside className="nw-topup-story">
                    <div className="nw-topup-story-copy">
                        <span className="nw-eyebrow">
                            <Crown size={13} className="text-yellow-400" /> ELITE PRESTIGE CLUB
                        </span>
                        <h2>Rise to glory.<br /><em>Claim your crown.</em></h2>
                        <p>Every spin & bet earns XP.<br />Unlock lifetime rewards with zero wager requirements.</p>

                        {/* Current User VIP Card */}
                        <div className="nw-topup-balance">
                            <div className="flex items-center justify-between text-[9px] text-[#b6a3c5] uppercase tracking-wider font-bold mb-1">
                                <span>Active Rank</span>
                                <span className="text-yellow-400 font-extrabold flex items-center gap-1">
                                    <Sparkles size={10} /> Tier {userLevel} of 5
                                </span>
                            </div>
                            
                            <strong className="text-lg text-white font-heading flex items-center gap-2">
                                <span>{currentTier.icon}</span>
                                <span className="truncate">{currentTier.name}</span>
                            </strong>

                            {/* Progress bar */}
                            <div className="mt-3 space-y-1">
                                <div className="flex justify-between text-[10px] text-gray-400 font-mono">
                                    <span>{userXp.toLocaleString()} XP</span>
                                    <span>{currentTier.maxXp.toLocaleString()} XP</span>
                                </div>
                                <div className="w-full bg-[#120e1c] h-2 rounded-full overflow-hidden border border-white/5">
                                    <div 
                                        className="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-yellow-400 rounded-full transition-all duration-500"
                                        style={{ width: `${progressPercent}%` }}
                                    />
                                </div>
                                <div className="text-[9px] text-purple-300 text-right pt-0.5">
                                    {100 - progressPercent}% to {nextTier?.name || 'Max Tier'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Trophy Art */}
                    <img 
                        src="/images/landing/neon-trophy.png" 
                        alt="VIP Trophy" 
                        className="nw-topup-art" 
                    />

                    <span className="nw-topup-story-foot">
                        <span>✦</span> 0X WAGER ON ALL VIP PERKS
                    </span>
                </aside>

                {/* Right Main Panel */}
                <div className="nw-topup-main">
                    <header className="nw-topup-heading">
                        <span className="nw-topup-kicker">
                            <Crown size={13} /> VIP PRIVILEGES LOUNGE 
                            <span><Sparkles size={11} /> 0x Wagering</span>
                        </span>
                        <h2 id="vip-title">Exclusive player status.</h2>
                        <p id="vip-description">Earn instant cashback, weekly rakeback, rank-up gifts, and priority cashouts.</p>
                    </header>

                    {/* Navigation Tabs */}
                    <div className="nw-topup-tabs" role="group" aria-label="VIP Club sections">
                        <button 
                            aria-pressed={activeTab === 'tiers'} 
                            onClick={() => setActiveTab('tiers')} 
                            className={activeTab === 'tiers' ? 'is-active' : ''}
                        >
                            <Trophy size={16} /> VIP Tiers <span>5 LEVELS</span>
                        </button>
                        <button 
                            aria-pressed={activeTab === 'stats'} 
                            onClick={() => setActiveTab('stats')} 
                            className={activeTab === 'stats' ? 'is-active' : ''}
                        >
                            <TrendingUp size={16} /> Radar & Stats
                        </button>
                        <button 
                            aria-pressed={activeTab === 'perks'} 
                            onClick={() => setActiveTab('perks')} 
                            className={activeTab === 'perks' ? 'is-active' : ''}
                        >
                            <Sparkles size={16} /> Elite Perks
                        </button>
                    </div>

                    {/* TAB 1: VIP TIERS */}
                    {activeTab === 'tiers' && (
                        <div className="mt-5 space-y-2.5">
                            <div className="nw-pack-heading">
                                <h3>Progression Tiers</h3>
                                <span>10,000 Coins Wagered = 1 VIP XP <Sparkles size={12} /></span>
                            </div>

                            <div className="space-y-2 max-h-[330px] overflow-y-auto pr-1">
                                {vipTiers.map(tier => {
                                    const isCurrent = userLevel === tier.level;
                                    const isUnlocked = userLevel >= tier.level;

                                    return (
                                        <div 
                                            key={tier.level}
                                            className={`nw-vip-tier-card ${isCurrent ? 'is-current' : !isUnlocked ? 'is-locked' : ''}`}
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="text-2xl">{tier.icon}</span>
                                                <div>
                                                    <div className="flex items-center gap-2">
                                                        <strong className="text-xs sm:text-sm text-white font-heading">
                                                            Level {tier.level}: {tier.name}
                                                        </strong>
                                                        {isCurrent && (
                                                            <span className="px-1.5 py-0.5 rounded bg-yellow-500/20 border border-yellow-500/50 text-[8px] font-extrabold text-yellow-300 uppercase tracking-wider">
                                                                YOU ARE HERE
                                                            </span>
                                                        )}
                                                    </div>
                                                    <div className="text-[10px] text-gray-400 font-mono">
                                                        {tier.minXp.toLocaleString()} - {tier.maxXp.toLocaleString()} XP
                                                    </div>
                                                </div>
                                            </div>

                                            <div className="flex items-center gap-3 sm:gap-4 text-xs font-mono">
                                                <div className="text-right">
                                                    <span className="block text-[8px] uppercase tracking-wider text-gray-400 font-sans">Cashback</span>
                                                    <span className="font-bold text-emerald-400">{tier.cashback}</span>
                                                </div>
                                                <div className="text-right">
                                                    <span className="block text-[8px] uppercase tracking-wider text-gray-400 font-sans">Rakeback</span>
                                                    <span className="font-bold text-cyan-400">{tier.rakeback}</span>
                                                </div>
                                                <div className="text-right">
                                                    <span className="block text-[8px] uppercase tracking-wider text-gray-400 font-sans">Rank Bonus</span>
                                                    <span className="font-bold text-yellow-400">{tier.bonus}</span>
                                                </div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>

                            {/* Summary strip */}
                            <div className="nw-topup-summary">
                                <div>
                                    <span>Your Current Rank Rewards</span>
                                    <strong>{currentTier.cashback} Cashback <small>+ {currentTier.rakeback} Weekly Rakeback</small></strong>
                                </div>
                                <div>
                                    <span>Next Rank Unlock</span>
                                    <strong>{nextTier?.bonus || 'Max Prestige'} <small>Bonus Coins</small></strong>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* TAB 2: PLAYER RADAR & STATS */}
                    {activeTab === 'stats' && (
                        <div className="mt-5 space-y-4">
                            <div className="nw-pack-heading">
                                <h3>Player Radar Metrics</h3>
                                <span>Live Skill & Activity Visualizer</span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 items-center">
                                {/* SVG Radar Polygon */}
                                <div className="p-4 rounded-xl bg-[#171221] border border-[#3b2d49] flex flex-col items-center justify-center relative min-h-[190px]">
                                    <div className="relative w-40 h-36 flex items-center justify-center">
                                        <svg viewBox="0 0 100 100" className="w-full h-full overflow-visible">
                                            <polygon points="50,10 90,38 75,82 25,82 10,38" fill="none" stroke="#322344" strokeWidth="1" />
                                            <polygon points="50,25 75,42 65,70 35,70 25,42" fill="none" stroke="#322344" strokeWidth="1" />
                                            
                                            {/* Filled Radar */}
                                            <polygon 
                                                points="50,18 85,38 70,75 30,78 20,35" 
                                                fill="rgba(147, 51, 234, 0.35)" 
                                                stroke="#a855f7" 
                                                strokeWidth="2"
                                            />

                                            <circle cx="50" cy="18" r="3" fill="#f4cd82" />
                                            <circle cx="85" cy="38" r="3" fill="#06b6d4" />
                                            <circle cx="70" cy="75" r="3" fill="#10b981" />
                                            <circle cx="30" cy="78" r="3" fill="#f59e0b" />
                                            <circle cx="20" cy="35" r="3" fill="#ec4899" />
                                        </svg>

                                        {/* Axis Labels */}
                                        <span className="absolute top-0 text-[8px] font-bold text-amber-300">Win Rate (78%)</span>
                                        <span className="absolute right-0 top-1/3 text-[8px] font-bold text-cyan-300">Volume</span>
                                        <span className="absolute bottom-0 right-1 text-[8px] font-bold text-emerald-300">Slots</span>
                                        <span className="absolute bottom-0 left-1 text-[8px] font-bold text-yellow-300">Live Table</span>
                                        <span className="absolute left-0 top-1/3 text-[8px] font-bold text-pink-300">Loyalty</span>
                                    </div>
                                </div>

                                {/* Stats Grid */}
                                <div className="grid grid-cols-2 gap-2.5">
                                    <div className="nw-vip-stat-box">
                                        <span className="text-[9px] text-gray-400 uppercase font-semibold">Lifetime Wager</span>
                                        <strong>1,250,000,000 <small className="text-xs text-yellow-400">Coins</small></strong>
                                    </div>
                                    <div className="nw-vip-stat-box">
                                        <span className="text-[9px] text-gray-400 uppercase font-semibold">Total Cashback</span>
                                        <strong className="text-emerald-400">34,550,000 <small className="text-xs text-emerald-300">Coins</small></strong>
                                    </div>
                                    <div className="nw-vip-stat-box">
                                        <span className="text-[9px] text-gray-400 uppercase font-semibold">Tournaments</span>
                                        <strong className="text-cyan-400">14 <small className="text-xs text-gray-400">entries</small></strong>
                                    </div>
                                    <div className="nw-vip-stat-box">
                                        <span className="text-[9px] text-gray-400 uppercase font-semibold">VIP Multiplier</span>
                                        <strong className="text-purple-400">1.25x <small className="text-xs text-gray-400">XP</small></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* TAB 3: ELITE PERKS */}
                    {activeTab === 'perks' && (
                        <div className="mt-5 space-y-3">
                            <div className="nw-pack-heading">
                                <h3>VIP Club Perks & Concierge</h3>
                                <span>Zero Wagering • Instant Payouts</span>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-[300px] overflow-y-auto pr-1">
                                <div className="nw-vip-perk-item">
                                    <div className="nw-vip-perk-icon"><ShieldCheck size={18} /></div>
                                    <div>
                                        <h4 className="text-xs font-bold text-white">0x Wager Bonuses</h4>
                                        <p className="text-[10px] text-gray-400 mt-0.5 leading-relaxed">
                                            All cashback, rakeback, and level-up rewards are credited as pure Coins ready to play.
                                        </p>
                                    </div>
                                </div>

                                <div className="nw-vip-perk-item">
                                    <div className="nw-vip-perk-icon"><Zap size={18} /></div>
                                    <div>
                                        <h4 className="text-xs font-bold text-white">Priority Redemptions</h4>
                                        <p className="text-[10px] text-gray-400 mt-0.5 leading-relaxed">
                                            Express processing on crypto and bank withdrawals with increased daily limits.
                                        </p>
                                    </div>
                                </div>

                                <div className="nw-vip-perk-item">
                                    <div className="nw-vip-perk-icon"><Headphones size={18} /></div>
                                    <div>
                                        <h4 className="text-xs font-bold text-white">Dedicated VIP Concierge</h4>
                                        <p className="text-[10px] text-gray-400 mt-0.5 leading-relaxed">
                                            Personal VIP host available 24/7 on Telegram, Discord, and private live chat.
                                        </p>
                                    </div>
                                </div>

                                <div className="nw-vip-perk-item">
                                    <div className="nw-vip-perk-icon"><Gift size={18} /></div>
                                    <div>
                                        <h4 className="text-xs font-bold text-white">Surprise Drop Boxes</h4>
                                        <p className="text-[10px] text-gray-400 mt-0.5 leading-relaxed">
                                            Weekly mystery boxes, reload bonuses, and birthday Coin gifts delivered to your inbox.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Bottom CTA / Action */}
                    <div className="nw-topup-checkout mt-5">
                        <Link 
                            href="/" 
                            onClick={onClose}
                            className="nw-button nw-button-purple w-full flex items-center justify-between"
                        >
                            <span>Play Slots & Level Up VIP</span>
                            <ArrowRight size={17} />
                        </Link>
                        <p>
                            <Sparkles size={12} className="text-yellow-400" /> XP is automatically earned on every game round in real-time.
                        </p>
                    </div>

                    <footer className="nw-topup-foot">
                        <span>18+ · Social Casino VIP Club</span>
                        <Link href="/sweeps-rules" onClick={onClose} className="hover:text-purple-300">
                            Sweeps rules <ChevronRight size={12} />
                        </Link>
                    </footer>
                </div>
            </section>
        </div>
    );
}
