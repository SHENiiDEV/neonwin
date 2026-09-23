import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import { 
    Crown, 
    Trophy, 
    Sparkles, 
    ShieldCheck, 
    Zap, 
    ArrowRight, 
    Coins, 
    Check, 
    Gift,
    Headphones,
    Flame,
    Lock,
    TrendingUp,
    ChevronRight,
    HelpCircle
} from 'lucide-react';

const defaultVipTiers = [
    { level: 1, name: 'Bronze Recruit', min_xp: 0, max_xp: 5000, cashback: '2%', rakeback: '5%', level_bonus: '25 SC', icon: '🥉' },
    { level: 2, name: 'Silver Shadow', min_xp: 5000, max_xp: 20000, cashback: '5%', rakeback: '8%', level_bonus: '100 SC', icon: '🥈' },
    { level: 3, name: 'Gold Ronin', min_xp: 20000, max_xp: 50000, cashback: '8%', rakeback: '12%', level_bonus: '300 SC', icon: '🥇' },
    { level: 4, name: 'Platinum Cyber', min_xp: 50000, max_xp: 100000, cashback: '12%', rakeback: '15%', level_bonus: '1,000 SC', icon: '💎' },
    { level: 5, name: 'Neon Overlord', min_xp: 100000, max_xp: 500000, cashback: '18%', rakeback: '20%', level_bonus: '5,000 SC', icon: '👑' },
];

export default function VipClub({ vipTiers = defaultVipTiers }) {
    const [openFaq, setOpenFaq] = useState(null);
    const tiers = vipTiers.length > 0 ? vipTiers : defaultVipTiers;

    const faqs = [
        {
            q: 'How do I earn VIP XP points?',
            a: 'For every 1 SC wagered on slots, live casino tables, or crash games, you automatically earn 1 VIP XP point in real time. XP never expires.'
        },
        {
            q: 'Are there any wagering requirements on VIP rewards?',
            a: 'None! All cashback, weekly rakeback, and level-up bonuses are credited as pure SC with 0x wager requirement.'
        },
        {
            q: 'When is cashback and rakeback credited?',
            a: 'Instant cashback is available immediately in your VIP dashboard. Weekly rakeback drops every Monday at 00:00 UTC.'
        },
        {
            q: 'How do I contact my personal VIP Concierge?',
            a: 'Once you reach Gold Ronin (Level 3) or above, a dedicated VIP Manager will be assigned to your account with direct 24/7 Telegram & Live Chat access.'
        }
    ];

    return (
        <MainLayout>
            {({ openDeposit, openAuth }) => (
                <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-10 py-4">
                    <Head title="VIP Club & Elite Rewards — Neonwin" />

                    {/* Hero Banner in Amethyst-Gold Style */}
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 md:p-14 flex flex-col md:flex-row items-center justify-between gap-8 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                        {/* Background Glow */}
                        <div className="absolute top-0 right-1/4 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none" />
                        <div className="absolute bottom-0 left-10 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

                        <div className="max-w-2xl relative z-10 text-center md:text-left">
                            <span className="nw-eyebrow mb-3 text-amber-300">
                                <Crown size={14} className="text-yellow-400" /> EXCLUSIVE PRESTIGE EXPERIENCE
                            </span>

                            <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-6xl text-white tracking-tight leading-[1.1] mb-4">
                                Rise to glory.<br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-amber-200 to-yellow-500">
                                    Claim your crown.
                                </span>
                            </h1>

                            <p className="text-xs sm:text-sm text-gray-300 font-medium max-w-xl leading-relaxed mb-6">
                                Play your favorite slots, live casino tables and crash games. Earn VIP XP on every spin to unlock up to 18% instant cashback, weekly rakeback, and lifetime gifts.
                            </p>

                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-4">
                                <Link 
                                    href="/" 
                                    className="nw-button nw-button-purple px-6 py-3 text-xs flex items-center gap-2"
                                >
                                    <span>Explore Games & Earn XP</span>
                                    <ArrowRight size={14} />
                                </Link>

                                <button 
                                    onClick={openDeposit}
                                    className="px-5 py-2.5 rounded-xl bg-[#21182e] hover:bg-[#2c203d] border border-[#4d365e] text-yellow-300 text-xs font-bold transition flex items-center gap-2"
                                >
                                    <Coins size={14} className="text-yellow-400" />
                                    <span>Get Coins Pack</span>
                                </button>
                            </div>
                        </div>

                        {/* Trophy Art */}
                        <div className="relative w-56 h-56 sm:w-64 sm:h-64 flex items-center justify-center flex-shrink-0">
                            <img 
                                src="/images/landing/neon-trophy.png" 
                                alt="Neonwin VIP Trophy" 
                                className="w-full h-full object-contain filter drop-shadow-[0_15px_30px_rgba(234,179,8,0.3)] animate-pulse"
                            />
                        </div>
                    </div>

                    {/* Quick Highlights Row */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="p-4 rounded-2xl bg-[#161220] border border-[#342742] flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-purple-900/40 border border-purple-500/30 flex items-center justify-center text-purple-300 flex-shrink-0">
                                <ShieldCheck size={20} />
                            </div>
                            <div>
                                <strong className="block text-xs font-bold text-white font-heading">0x Wagering</strong>
                                <span className="text-[10px] text-gray-400">Pure Coins bonuses</span>
                            </div>
                        </div>

                        <div className="p-4 rounded-2xl bg-[#161220] border border-[#342742] flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-amber-900/40 border border-amber-500/30 flex items-center justify-center text-yellow-400 flex-shrink-0">
                                <Coins size={20} />
                            </div>
                            <div>
                                <strong className="block text-xs font-bold text-white font-heading">Up to 18% Cashback</strong>
                                <span className="text-[10px] text-gray-400">Instant on all spins</span>
                            </div>
                        </div>

                        <div className="p-4 rounded-2xl bg-[#161220] border border-[#342742] flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-cyan-900/40 border border-cyan-500/30 flex items-center justify-center text-cyan-300 flex-shrink-0">
                                <Zap size={20} />
                            </div>
                            <div>
                                <strong className="block text-xs font-bold text-white font-heading">Weekly Rakeback</strong>
                                <span className="text-[10px] text-gray-400">Drops every Monday</span>
                            </div>
                        </div>

                        <div className="p-4 rounded-2xl bg-[#161220] border border-[#342742] flex items-center gap-3">
                            <div className="w-10 h-10 rounded-xl bg-pink-900/40 border border-pink-500/30 flex items-center justify-center text-pink-300 flex-shrink-0">
                                <Headphones size={20} />
                            </div>
                            <div>
                                <strong className="block text-xs font-bold text-white font-heading">VIP Concierge</strong>
                                <span className="text-[10px] text-gray-400">24/7 Dedicated manager</span>
                            </div>
                        </div>
                    </div>

                    {/* VIP Tiers Grid */}
                    <div className="flex flex-col gap-5">
                        <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                            <div>
                                <h2 className="font-heading font-black text-xl sm:text-2xl text-white">
                                    VIP Progression Tiers
                                </h2>
                                <p className="text-xs text-gray-400">
                                    Unlock greater cashback, weekly rakeback, and rank cash bonuses as you level up.
                                </p>
                            </div>
                            <span className="text-xs text-purple-300 font-mono bg-purple-950/60 border border-purple-800/40 px-3 py-1 rounded-full">
                                10,000 Coins = 1 XP
                            </span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                            {tiers.map((tier, idx) => (
                                <div 
                                    key={tier.level}
                                    className={`relative p-6 rounded-2xl border transition-all duration-300 flex flex-col justify-between ${
                                        idx === 2 
                                            ? 'bg-gradient-to-b from-[#231836] to-[#14101e] border-yellow-500/50 shadow-[0_0_30px_rgba(234,179,8,0.15)]' 
                                            : 'bg-[#15121e] border-[#362744] hover:border-purple-500/40'
                                    }`}
                                >
                                    {idx === 2 && (
                                        <span className="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-0.5 rounded-full bg-yellow-400 text-black text-[9px] font-black uppercase tracking-wider shadow-md">
                                            POPULAR MILESTONE
                                        </span>
                                    )}

                                    <div>
                                        <div className="flex items-center justify-between mb-4">
                                            <span className="text-3xl">{tier.icon}</span>
                                            <span className="px-2.5 py-0.5 rounded-full bg-purple-950/80 border border-purple-500/30 text-[9px] font-black text-purple-300 uppercase tracking-wider">
                                                LEVEL {tier.level}
                                            </span>
                                        </div>

                                        <h3 className="font-heading font-black text-lg text-white mb-1">
                                            {tier.name}
                                        </h3>
                                        <div className="text-xs text-gray-400 font-mono mb-4">
                                            {tier.min_xp?.toLocaleString()} - {tier.max_xp?.toLocaleString()} XP
                                        </div>

                                        <div className="space-y-2 text-xs">
                                            <div className="flex justify-between p-2.5 rounded-xl bg-black/30 border border-white/5">
                                                <span className="text-gray-400">Instant Cashback</span>
                                                <span className="font-bold text-emerald-400">{tier.cashback}</span>
                                            </div>
                                            <div className="flex justify-between p-2.5 rounded-xl bg-black/30 border border-white/5">
                                                <span className="text-gray-400">Weekly Rakeback</span>
                                                <span className="font-bold text-cyan-400">{tier.rakeback}</span>
                                            </div>
                                            <div className="flex justify-between p-2.5 rounded-xl bg-black/30 border border-white/5">
                                                <span className="text-gray-400">Rank Bonus</span>
                                                <span className="font-bold text-yellow-400">{tier.level_bonus}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="mt-6 pt-4 border-t border-white/5">
                                        <span className="text-[10px] text-gray-400 flex items-center gap-1.5">
                                            <Check size={12} className="text-emerald-400" />
                                            <span>Lifetime Status • Never Downgrades</span>
                                        </span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* FAQ Section */}
                    <div className="p-6 sm:p-8 rounded-3xl bg-[#14111d] border border-[#362744] flex flex-col gap-5">
                        <div className="flex items-center gap-3">
                            <HelpCircle size={20} className="text-yellow-400" />
                            <div>
                                <h3 className="font-heading font-black text-lg text-white">VIP Club Frequently Asked Questions</h3>
                                <p className="text-xs text-gray-400">Everything you need to know about XP and bonuses</p>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            {faqs.map((faq, index) => (
                                <div 
                                    key={index}
                                    className="p-4 rounded-2xl bg-[#1a1425] border border-[#3b2c4c]"
                                >
                                    <h4 className="text-xs font-bold text-white mb-1.5 flex items-center gap-2">
                                        <span className="text-yellow-400">Q:</span> {faq.q}
                                    </h4>
                                    <p className="text-[11px] text-gray-300 leading-relaxed">
                                        {faq.a}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            )}
        </MainLayout>
    );
}
