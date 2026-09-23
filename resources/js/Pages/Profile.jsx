import React, { useState, useEffect } from 'react';
import { Head, Link, usePage, useForm } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import { 
    User as UserIcon, 
    Crown, 
    Trophy, 
    Coins, 
    TrendingUp, 
    Flame, 
    ShieldCheck, 
    Zap, 
    Calendar, 
    MapPin, 
    Phone, 
    Mail, 
    Key, 
    History, 
    CheckCircle2, 
    ArrowUpRight, 
    Lock, 
    Edit3, 
    Gift, 
    ChevronRight,
    Sparkles,
    Eye,
    Gamepad2,
    RefreshCw,
    Users,
    Copy,
    Send,
    Box,
    QrCode
} from 'lucide-react';
import axios from 'axios';


export default function Profile({ stats = {}, transactions = [], vipTiers = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const queryTab = typeof window !== 'undefined' ? new URLSearchParams(window.location.search).get('tab') : null;
    const [activeTab, setActiveTab] = useState(queryTab || 'overview'); // 'overview' | 'history' | 'syndicate' | 'personal' | 'security'
    const [syndicateData, setSyndicateData] = useState(null);
    const [copiedRef, setCopiedRef] = useState(false);

    useEffect(() => {
        if (activeTab === 'syndicate' && !syndicateData) {
            axios.get('/api/syndicate')
                .then(res => setSyndicateData(res.data))
                .catch(err => console.error(err));
        }
    }, [activeTab]);

    const copyReferralLink = () => {
        const link = syndicateData?.referral_url || `${window.location.origin}/?ref=${user?.referral_code}`;
        navigator.clipboard.writeText(link);
        setCopiedRef(true);
        setTimeout(() => setCopiedRef(false), 2000);
    };

    // Form for updating profile info
    const profileForm = useForm({
        name: user?.name || '',
        surname: user?.surname || '',
        phone: user?.phone || '',
        street: user?.street || '',
        city: user?.city || '',
        postcode: user?.postcode || '',
    });

    // Form for updating password
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const handleProfileSubmit = (e) => {
        e.preventDefault();
        profileForm.post('/profile', {
            preserveScroll: true,
        });
    };

    const handlePasswordSubmit = (e) => {
        e.preventDefault();
        passwordForm.post('/profile/password', {
            preserveScroll: true,
            onSuccess: () => passwordForm.reset(),
        });
    };

    const currentTier = stats.current_tier || { level: 1, name: 'Bronze Recruit', icon: '🥉' };
    const nextTier = stats.next_tier;
    const currentXp = stats.vip_xp || 0;
    const minXp = currentTier.min_xp || 0;
    const maxXp = nextTier ? nextTier.min_xp : (currentTier.max_xp || 100000);
    const progressPercent = nextTier 
        ? Math.min(100, Math.max(0, Math.round(((currentXp - minXp) / (maxXp - minXp)) * 100))) 
        : 100;

    const tierColors = {
        1: { border: 'border-amber-700/50', text: 'text-amber-400', bg: 'bg-amber-950/40', glow: 'shadow-[0_0_20px_rgba(180,83,9,0.2)]' },
        2: { border: 'border-slate-400/50', text: 'text-slate-300', bg: 'bg-slate-900/40', glow: 'shadow-[0_0_20px_rgba(148,163,184,0.2)]' },
        3: { border: 'border-yellow-500/50', text: 'text-yellow-400', bg: 'bg-yellow-950/40', glow: 'shadow-[0_0_20px_rgba(234,179,8,0.25)]' },
        4: { border: 'border-cyan-500/50', text: 'text-cyan-400', bg: 'bg-cyan-950/40', glow: 'shadow-[0_0_20px_rgba(6,182,212,0.25)]' },
        5: { border: 'border-purple-500/60', text: 'text-purple-400', bg: 'bg-purple-950/40', glow: 'shadow-[0_0_25px_rgba(168,85,247,0.3)]' },
    };

    const currentStyle = tierColors[currentTier.level] || tierColors[1];

    const formatCoins = (val) => Math.floor(Number(val || 0)).toLocaleString('en-US');
    const formatSc = formatCoins;

    return (
        <MainLayout>
            {({ openDeposit, openVip, openCrates, openVault, openTip }) => (
                <div className="w-full max-w-[1440px] mx-auto flex flex-col gap-8 py-4">
                    <Head title={`Player Profile: ${user?.name || 'Player'} — Neonwin`} />

                    {/* Top Player Hero Banner */}
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1b122c] via-[#141022] to-[#0c0915] border border-[#3e2c52] p-6 sm:p-10 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                        {/* Background Glow Accents */}
                        <div className="absolute top-0 right-10 w-96 h-96 bg-purple-600/15 rounded-full blur-3xl pointer-events-none" />
                        <div className="absolute bottom-0 left-1/3 w-80 h-80 bg-amber-500/10 rounded-full blur-3xl pointer-events-none" />

                        <div className="relative z-10 flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                            
                            {/* Player Info Left */}
                            <div className="flex items-center gap-5 sm:gap-6">
                                {/* Glowing Avatar */}
                                <div className="relative flex-shrink-0">
                                    <div className={`w-20 h-20 sm:w-24 sm:h-24 rounded-2xl overflow-hidden border-2 ${currentStyle.border} ${currentStyle.glow} bg-[#0c0914] flex items-center justify-center`}>
                                        {user?.avatar ? (
                                            <img 
                                                src={user.avatar} 
                                                alt={user.name} 
                                                className="w-full h-full object-cover" 
                                            />
                                        ) : (
                                            <span className="text-3xl font-black text-purple-300">
                                                {user?.name?.charAt(0) || 'P'}
                                            </span>
                                        )}
                                    </div>
                                    <span className="absolute -bottom-2 -right-2 px-2 py-0.5 rounded-lg bg-yellow-400 text-black text-[10px] font-black uppercase tracking-wider shadow-md flex items-center gap-1">
                                        <span>{currentTier.icon}</span> L{currentTier.level}
                                    </span>
                                </div>

                                {/* Names & Meta */}
                                <div className="flex flex-col">
                                    <div className="flex items-center gap-2.5 flex-wrap">
                                        <h1 className="font-heading font-black text-2xl sm:text-3xl text-white tracking-tight">
                                            {user?.name} {user?.surname || ''}
                                        </h1>
                                        <span className="px-2.5 py-0.5 rounded-full bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 text-[10px] font-bold flex items-center gap-1">
                                            <ShieldCheck size={12} /> KYC Verified
                                        </span>
                                    </div>

                                    <div className="flex items-center gap-3 mt-1.5 text-xs text-gray-400 font-mono flex-wrap">
                                        <span className="text-purple-400 font-bold">{user?.user_code || 'NW-PLAYER'}</span>
                                        <span>•</span>
                                        <span className="text-gray-300">{user?.email}</span>
                                        {user?.country && (
                                            <>
                                                <span>•</span>
                                                <span className="flex items-center gap-1 text-gray-300 font-sans">
                                                    <MapPin size={11} className="text-amber-400" />
                                                    {user.city ? `${user.city}, ` : ''}{user.country}
                                                </span>
                                            </>
                                        )}
                                    </div>

                                    <div className="flex items-center gap-2 mt-2.5">
                                        <span className="text-[11px] text-gray-400">VIP Rank:</span>
                                        <span className={`text-xs font-black uppercase tracking-wider ${currentStyle.text}`}>
                                            {currentTier.name}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Wallet & Actions Right */}
                            <div className="flex flex-wrap items-center gap-3 w-full lg:w-auto justify-between lg:justify-end pt-4 lg:pt-0 border-t lg:border-t-0 border-white/10">
                                <div className="p-3 sm:p-3.5 rounded-2xl bg-[#0e0a19]/80 border border-[#3e2c52] flex flex-col min-w-[130px]">
                                    <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Coins Wallet</span>
                                    <span className="font-heading font-black text-xl text-yellow-300 flex items-baseline gap-1">
                                        {formatCoins(stats.balance)} <small className="text-xs text-yellow-500 font-mono">Coins</small>
                                    </span>
                                </div>

                                <div className="p-3 sm:p-3.5 rounded-2xl bg-cyan-950/30 border border-cyan-500/30 flex flex-col min-w-[130px]">
                                    <span className="text-[10px] text-cyan-300 uppercase tracking-wider font-bold">Cyber Vault</span>
                                    <span className="font-heading font-black text-xl text-cyan-300 flex items-baseline gap-1">
                                        {formatCoins(user?.vault_balance)} <small className="text-xs text-cyan-400 font-mono">Coins</small>
                                    </span>
                                </div>

                                <div className="flex flex-wrap gap-2">
                                    <button 
                                        onClick={openDeposit}
                                        className="nw-button nw-button-purple px-4 py-2.5 text-xs flex items-center justify-center gap-1.5 shadow-md"
                                    >
                                        <Coins size={14} />
                                        <span>Get Coins</span>
                                    </button>
                                    
                                    <button 
                                        onClick={openCrates}
                                        className="px-3 py-2.5 rounded-xl bg-[#2a1740] hover:bg-[#382055] border border-purple-500/40 text-purple-300 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <Box size={14} className="text-yellow-400" />
                                        <span>Crates ({user?.pending_crates_count || 0})</span>
                                    </button>

                                    <button 
                                        onClick={openVault}
                                        className="px-3 py-2.5 rounded-xl bg-cyan-950/50 hover:bg-cyan-900/50 border border-cyan-500/40 text-cyan-300 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <Lock size={14} />
                                        <span>Vault</span>
                                    </button>

                                    <button 
                                        onClick={openTip}
                                        className="px-3 py-2.5 rounded-xl bg-pink-950/50 hover:bg-pink-900/50 border border-pink-500/40 text-pink-300 text-xs font-bold transition flex items-center justify-center gap-1.5"
                                    >
                                        <Send size={14} />
                                        <span>Tip</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </div>

                    {/* Key Stats Metric Grid (6 cards) */}
                    <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3.5 sm:gap-4">
                        {/* 1. Play Balance */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Play Balance</span>
                                <Coins size={15} className="text-yellow-400" />
                            </div>
                            <span className="font-heading font-black text-lg sm:text-xl text-yellow-300">
                                {formatCoins(stats.balance)} <small className="text-[10px] text-yellow-500">Coins</small>
                            </span>
                        </div>

                        {/* 2. Total Wagered */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Total Wagered</span>
                                <Zap size={15} className="text-purple-400" />
                            </div>
                            <span className="font-heading font-black text-lg sm:text-xl text-white">
                                {formatCoins(stats.total_bets)} <small className="text-[10px] text-purple-400">Coins</small>
                            </span>
                        </div>

                        {/* 3. Total Won */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Total Won</span>
                                <Trophy size={15} className="text-emerald-400" />
                            </div>
                            <span className="font-heading font-black text-lg sm:text-xl text-emerald-400">
                                {formatCoins(stats.total_wins)} <small className="text-[10px] text-emerald-500">Coins</small>
                            </span>
                        </div>

                        {/* 4. Biggest Win */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Biggest Win</span>
                                <Flame size={15} className="text-amber-400" />
                            </div>
                            <div className="flex flex-col">
                                <span className="font-heading font-black text-lg sm:text-xl text-amber-300">
                                    {formatCoins(stats.biggest_win?.amount)} <small className="text-[10px] text-amber-500">Coins</small>
                                </span>
                                {stats.biggest_win?.multiplier > 0 && (
                                    <span className="text-[10px] text-amber-400/80 font-mono">
                                        {stats.biggest_win.multiplier}x Multiplier
                                    </span>
                                )}
                            </div>
                        </div>

                        {/* 5. Return Rate (RTP) */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Player RTP</span>
                                <TrendingUp size={15} className="text-cyan-400" />
                            </div>
                            <span className="font-heading font-black text-lg sm:text-xl text-cyan-300">
                                {Number(stats.rtp || 96.5).toFixed(1)}%
                            </span>
                        </div>

                        {/* 6. Total Rounds */}
                        <div className="p-4 rounded-2xl bg-[#14101e] border border-[#342642] flex flex-col justify-between">
                            <div className="flex items-center justify-between text-gray-400 mb-2">
                                <span className="text-[10px] uppercase font-bold tracking-wider">Spins Played</span>
                                <Gamepad2 size={15} className="text-pink-400" />
                            </div>
                            <span className="font-heading font-black text-lg sm:text-xl text-pink-300">
                                {(stats.total_rounds || 0).toLocaleString()}
                            </span>
                        </div>
                    </div>

                    {/* Navigation Tabs */}
                    <div className="flex items-center gap-2 border-b border-[#2e213d] pb-2 overflow-x-auto no-scrollbar">
                        <button
                            onClick={() => setActiveTab('overview')}
                            className={`px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 ${
                                activeTab === 'overview'
                                    ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]'
                                    : 'bg-[#151120] text-gray-400 hover:text-white hover:bg-[#1e172e]'
                            }`}
                        >
                            <Sparkles size={14} />
                            <span>Overview & VIP Progress</span>
                        </button>

                        <button
                            onClick={() => setActiveTab('history')}
                            className={`px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 ${
                                activeTab === 'history'
                                    ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]'
                                    : 'bg-[#151120] text-gray-400 hover:text-white hover:bg-[#1e172e]'
                            }`}
                        >
                            <History size={14} />
                            <span>Gameplay History ({transactions.length})</span>
                        </button>

                        <button
                            onClick={() => setActiveTab('syndicate')}
                            className={`px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 ${
                                activeTab === 'syndicate'
                                    ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]'
                                    : 'bg-[#151120] text-gray-400 hover:text-white hover:bg-[#1e172e]'
                            }`}
                        >
                            <Users size={14} className="text-yellow-400" />
                            <span>Cyber Syndicate (Referrals)</span>
                        </button>

                        <button
                            onClick={() => setActiveTab('personal')}
                            className={`px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 ${
                                activeTab === 'personal'
                                    ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]'
                                    : 'bg-[#151120] text-gray-400 hover:text-white hover:bg-[#1e172e]'
                            }`}
                        >
                            <UserIcon size={14} />
                            <span>Personal Details & KYC</span>
                        </button>

                        <button
                            onClick={() => setActiveTab('security')}
                            className={`px-4 py-2.5 rounded-xl text-xs font-bold transition flex items-center gap-2 flex-shrink-0 ${
                                activeTab === 'security'
                                    ? 'bg-purple-600 text-white shadow-[0_0_15px_rgba(168,85,247,0.4)]'
                                    : 'bg-[#151120] text-gray-400 hover:text-white hover:bg-[#1e172e]'
                            }`}
                        >
                            <Lock size={14} />
                            <span>Security & Limits</span>
                        </button>
                    </div>

                    {/* TAB 1: OVERVIEW & VIP PROGRESS */}
                    {activeTab === 'overview' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {/* VIP Progress Tracker (2 cols) */}
                            <div className="lg:col-span-2 p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col gap-6">
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="w-10 h-10 rounded-xl bg-yellow-950/60 border border-yellow-500/30 flex items-center justify-center text-yellow-400">
                                            <Crown size={20} />
                                        </div>
                                        <div>
                                            <h3 className="font-heading font-black text-lg text-white">VIP Club Progress</h3>
                                            <p className="text-xs text-gray-400">Earn 1 VIP XP per 10,000 Coins wagered</p>
                                        </div>
                                    </div>

                                    <button 
                                        onClick={openVip}
                                        className="text-xs text-yellow-400 hover:text-yellow-300 font-bold flex items-center gap-1"
                                    >
                                        <span>View all tiers</span>
                                        <ChevronRight size={14} />
                                    </button>
                                </div>

                                {/* Progress Bar Container */}
                                <div className="p-5 rounded-2xl bg-[#1b1429] border border-[#3b2a4e] flex flex-col gap-3">
                                    <div className="flex items-center justify-between text-xs">
                                        <div className="flex items-center gap-2">
                                            <span className="text-base">{currentTier.icon}</span>
                                            <span className="font-bold text-white">{currentTier.name} (Level {currentTier.level})</span>
                                        </div>
                                        {nextTier ? (
                                            <div className="flex items-center gap-2">
                                                <span className="text-gray-400">Next:</span>
                                                <span className="font-bold text-yellow-300">{nextTier.name} (Level {nextTier.level})</span>
                                                <span className="text-base">{nextTier.icon}</span>
                                            </div>
                                        ) : (
                                            <span className="text-xs font-bold text-purple-300 uppercase">MAX VIP REACHED</span>
                                        )}
                                    </div>

                                    {/* Progress track */}
                                    <div className="w-full h-3.5 rounded-full bg-black/40 border border-white/10 overflow-hidden p-0.5 relative">
                                        <div 
                                            className="h-full rounded-full bg-gradient-to-r from-yellow-500 via-amber-400 to-yellow-300 shadow-[0_0_12px_rgba(234,179,8,0.6)] transition-all duration-500"
                                            style={{ width: `${progressPercent}%` }}
                                        />
                                    </div>

                                    <div className="flex items-center justify-between text-[11px] font-mono text-gray-400">
                                        <span>{currentXp.toLocaleString()} XP</span>
                                        <span>{progressPercent}% completed</span>
                                        {nextTier && <span>{(nextTier.min_xp - currentXp).toLocaleString()} XP to Level {nextTier.level}</span>}
                                    </div>
                                </div>

                                {/* Current Tier Perks */}
                                <div>
                                    <h4 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">
                                        Active Level {currentTier.level} Benefits
                                    </h4>
                                    <div className="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                        <div className="p-3.5 rounded-xl bg-[#1a1427] border border-white/5 flex flex-col">
                                            <span className="text-[10px] text-gray-400">Instant Cashback</span>
                                            <span className="font-bold text-emerald-400 text-sm mt-1">{currentTier.cashback}</span>
                                        </div>
                                        <div className="p-3.5 rounded-xl bg-[#1a1427] border border-white/5 flex flex-col">
                                            <span className="text-[10px] text-gray-400">Weekly Rakeback</span>
                                            <span className="font-bold text-cyan-400 text-sm mt-1">{currentTier.rakeback}</span>
                                        </div>
                                        <div className="p-3.5 rounded-xl bg-[#1a1427] border border-white/5 flex flex-col">
                                            <span className="text-[10px] text-gray-400">Rank Bonus</span>
                                            <span className="font-bold text-yellow-400 text-sm mt-1">{currentTier.level_bonus}</span>
                                        </div>
                                        <div className="p-3.5 rounded-xl bg-[#1a1427] border border-white/5 flex flex-col">
                                            <span className="text-[10px] text-gray-400">Wager Req.</span>
                                            <span className="font-bold text-purple-300 text-sm mt-1">0x (Pure Coins)</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Action Side Panel */}
                            <div className="p-6 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col justify-between gap-6">
                                <div>
                                    <div className="flex items-center gap-2.5 mb-4">
                                        <Gift size={18} className="text-purple-400" />
                                        <h3 className="font-heading font-black text-base text-white">Daily & Weekly Rewards</h3>
                                    </div>
                                    <p className="text-xs text-gray-300 leading-relaxed mb-4">
                                        Don't forget to claim your free daily login bonus and climb the leaderboard in active community tournaments.
                                    </p>

                                    <div className="space-y-2.5">
                                        <button 
                                            onClick={openDeposit}
                                            className="w-full p-3 rounded-xl bg-gradient-to-r from-purple-900/40 to-pink-900/30 border border-purple-500/30 hover:border-purple-400 text-left flex items-center justify-between transition group"
                                        >
                                            <div className="flex items-center gap-2.5">
                                                <span className="text-lg">🎁</span>
                                                <div>
                                                    <strong className="block text-xs text-white">Daily Free Claim</strong>
                                                    <span className="text-[10px] text-purple-300">100,000 Coins every 24h</span>
                                                </div>
                                            </div>
                                            <ArrowUpRight size={14} className="text-purple-400 group-hover:translate-x-0.5 transition" />
                                        </button>

                                        <Link 
                                             href="/#tournaments-section"
                                            className="w-full p-3 rounded-xl bg-[#1c152a] border border-[#3b2a4e] hover:border-amber-500/40 text-left flex items-center justify-between transition group"
                                        >
                                            <div className="flex items-center gap-2.5">
                                                <span className="text-lg">🏆</span>
                                                <div>
                                                    <strong className="block text-xs text-white">Tournaments</strong>
                                                    <span className="text-[10px] text-amber-300">Prize pools up to 1,000,000,000 Coins</span>
                                                </div>
                                            </div>
                                            <ArrowUpRight size={14} className="text-amber-400 group-hover:translate-x-0.5 transition" />
                                        </Link>
                                    </div>
                                </div>

                                <div className="p-4 rounded-2xl bg-black/40 border border-white/5 flex items-center gap-3">
                                    <ShieldCheck size={20} className="text-emerald-400 flex-shrink-0" />
                                    <div className="text-[11px] text-gray-400">
                                        <strong className="block text-white">Player Safety Certified</strong>
                                        Fair RNG certified & transparent sweeps engine.
                                    </div>
                                </div>
                            </div>

                        </div>
                    )}

                    {/* TAB 2: GAMEPLAY & BETTING HISTORY */}
                    {activeTab === 'history' && (
                        <div className="p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col gap-5">
                            <div className="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-2">
                                <div>
                                    <h3 className="font-heading font-black text-lg text-white">Recent Gameplay Ledger</h3>
                                    <p className="text-xs text-gray-400">Transparent real-time records of your spins, bets, and payouts</p>
                                </div>
                                <span className="text-xs font-mono text-purple-300 bg-purple-950/60 border border-purple-800/40 px-3 py-1 rounded-full">
                                    Last {transactions.length} rounds
                                </span>
                            </div>

                            {transactions.length > 0 ? (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-left text-xs">
                                        <thead>
                                            <tr className="border-b border-[#2e213d] text-gray-400 font-mono text-[10px] uppercase tracking-wider">
                                                <th className="py-3 px-3">Date & Time</th>
                                                <th className="py-3 px-3">Game / Provider</th>
                                                <th className="py-3 px-3">Bet</th>
                                                <th className="py-3 px-3">Payout</th>
                                                <th className="py-3 px-3">Multiplier</th>
                                                <th className="py-3 px-3">Balance After</th>
                                                <th className="py-3 px-3 text-right">Result</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-white/5">
                                            {transactions.map((tx) => {
                                                const isWin = tx.win_amount > tx.bet_amount;
                                                const multiplier = tx.bet_amount > 0 ? (tx.win_amount / tx.bet_amount).toFixed(1) : '-';
                                                const dateStr = tx.created_at ? new Date(tx.created_at).toLocaleString('en-US', {
                                                    month: 'short',
                                                    day: 'numeric',
                                                    hour: '2-digit',
                                                    minute: '2-digit'
                                                }) : 'Recent';

                                                return (
                                                    <tr key={tx.id} className="hover:bg-white/[0.02] transition">
                                                        <td className="py-3.5 px-3 font-mono text-gray-400 whitespace-nowrap">
                                                            {dateStr}
                                                        </td>
                                                        <td className="py-3.5 px-3 font-bold text-white whitespace-nowrap">
                                                            <div className="flex items-center gap-2">
                                                                <span className="w-2 h-2 rounded-full bg-purple-500" />
                                                                <span className="capitalize">{tx.game_code ? tx.game_code.replace(/^vs\d+/, '').replaceAll('_', ' ') : 'Spin'}</span>
                                                                <span className="text-[10px] text-gray-400 font-mono uppercase">{tx.provider_code}</span>
                                                            </div>
                                                        </td>
                                                        <td className="py-3.5 px-3 font-mono text-gray-300">
                                                            {formatCoins(tx.bet_amount)} Coins
                                                        </td>
                                                        <td className={`py-3.5 px-3 font-mono font-bold ${isWin ? 'text-emerald-400' : 'text-gray-400'}`}>
                                                            {formatCoins(tx.win_amount)} Coins
                                                        </td>
                                                        <td className="py-3.5 px-3 font-mono text-gray-400">
                                                            {multiplier !== '-' ? `${multiplier}x` : '—'}
                                                        </td>
                                                        <td className="py-3.5 px-3 font-mono text-yellow-300/90">
                                                            {formatCoins(tx.balance_after)} Coins
                                                        </td>
                                                        <td className="py-3.5 px-3 text-right">
                                                            {isWin ? (
                                                                <span className="px-2 py-0.5 rounded bg-emerald-950/80 border border-emerald-500/40 text-emerald-400 text-[10px] font-black uppercase">
                                                                    WIN
                                                                </span>
                                                            ) : (
                                                                <span className="px-2 py-0.5 rounded bg-gray-900 border border-white/10 text-gray-400 text-[10px] font-bold uppercase">
                                                                    PLAYED
                                                                </span>
                                                            )}
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <div className="p-12 text-center flex flex-col items-center gap-3">
                                    <Gamepad2 size={36} className="text-gray-600" />
                                    <h4 className="font-heading font-bold text-white text-sm">No game rounds recorded yet</h4>
                                    <p className="text-xs text-gray-400 max-w-sm">
                                        Choose any slot, crash game, or live table from the casino lobby to start playing and climbing VIP tiers!
                                    </p>
                                    <Link href="/" className="nw-button nw-button-purple mt-2 text-xs">
                                        Browse Casino Lobby
                                    </Link>
                                </div>
                            )}
                        </div>
                    )}

                    {/* TAB: CYBER SYNDICATE (REFERRAL PROGRAM) */}
                    {activeTab === 'syndicate' && (
                        <div className="flex flex-col gap-6">
                            
                            {/* Syndicate Hero */}
                            <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#201334] via-[#161022] to-[#0f0b18] border border-yellow-500/30 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_15px_50px_rgba(0,0,0,0.6)]">
                                <div className="max-w-xl">
                                    <span className="text-[10px] font-black uppercase text-yellow-400 tracking-wider flex items-center gap-1.5 mb-2">
                                        <Users size={14} /> CYBER SYNDICATE NETWORK
                                    </span>
                                    <h3 className="font-heading font-black text-2xl sm:text-3xl text-white">
                                        Build your crew. <span className="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-amber-200 to-yellow-500">Earn lifetime Coins.</span>
                                    </h3>
                                    <p className="text-xs text-gray-300 mt-2 leading-relaxed">
                                        Invite friends to Neonwin. Get <strong className="text-yellow-300">500,000 Coins</strong> for every verified signup + ongoing syndicate rakeback shares on their gameplay!
                                    </p>
                                </div>

                                <div className="grid grid-cols-2 gap-3 w-full md:w-auto flex-shrink-0">
                                    <div className="p-4 rounded-2xl bg-black/40 border border-yellow-500/20 text-center min-w-[120px]">
                                        <span className="text-[10px] text-gray-400 uppercase font-bold block">Recruits</span>
                                        <strong className="font-heading font-black text-2xl text-white">
                                            {syndicateData?.total_referrals ?? 0}
                                        </strong>
                                    </div>
                                    <div className="p-4 rounded-2xl bg-black/40 border border-yellow-500/20 text-center min-w-[120px]">
                                        <span className="text-[10px] text-gray-400 uppercase font-bold block">Total Earned</span>
                                        <strong className="font-heading font-black text-2xl text-yellow-300">
                                            {Math.floor(Number(syndicateData?.total_earned_sc ?? 0)).toLocaleString()} <small className="text-xs">Coins</small>
                                        </strong>
                                    </div>
                                </div>
                            </div>

                            {/* Referral Link & Code Box */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="p-6 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col justify-between gap-4">
                                    <div>
                                        <h4 className="font-heading font-bold text-sm text-white mb-1">Your Personal Syndicate Link</h4>
                                        <p className="text-xs text-gray-400 mb-4">Share this link directly with friends to automatically attribute them to your syndicate.</p>
                                        
                                        <div className="flex items-center gap-2 p-2 rounded-xl bg-black/40 border border-white/10 font-mono text-xs">
                                            <span className="flex-1 truncate text-gray-300 px-2">
                                                {syndicateData?.referral_url || `https://neonwin.com/?ref=${user?.referral_code || 'NW-REF'}`}
                                            </span>
                                            <button 
                                                onClick={copyReferralLink}
                                                className="px-3 py-2 rounded-lg bg-yellow-400 hover:bg-yellow-300 text-black font-bold text-xs flex items-center gap-1.5 transition flex-shrink-0"
                                            >
                                                <Copy size={13} /> {copiedRef ? 'Copied!' : 'Copy Link'}
                                            </button>
                                        </div>
                                    </div>

                                    <div className="pt-3 border-t border-white/5 flex items-center justify-between text-xs">
                                        <span className="text-gray-400">Your Referral Code:</span>
                                        <span className="font-mono font-bold text-yellow-300 bg-yellow-950/60 border border-yellow-500/30 px-2.5 py-0.5 rounded-lg">
                                            {user?.referral_code || 'NW-REF-XXXX'}
                                        </span>
                                    </div>
                                </div>

                                <div className="p-6 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col justify-between gap-4">
                                    <div className="flex items-start justify-between">
                                        <div>
                                            <h4 className="font-heading font-bold text-sm text-white mb-1">Mobile Scan & Invite</h4>
                                            <p className="text-xs text-gray-400">Scan QR Code directly with any smartphone camera</p>
                                        </div>
                                        <QrCode size={24} className="text-yellow-400" />
                                    </div>

                                    <div className="p-4 rounded-2xl bg-black/50 border border-white/5 flex items-center gap-4">
                                        <div className="w-16 h-16 rounded-xl bg-white p-1.5 flex items-center justify-center flex-shrink-0">
                                            <img 
                                                src={`https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=${encodeURIComponent(syndicateData?.referral_url || `https://neonwin.com/?ref=${user?.referral_code}`)}`} 
                                                alt="Referral QR Code"
                                                className="w-full h-full object-contain"
                                            />
                                        </div>
                                        <div className="text-xs text-gray-300 leading-relaxed">
                                            Instant signup attribution with automatic bonus credit on verification.
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Active Recruits List */}
                            <div className="p-6 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col gap-4">
                                <h4 className="font-heading font-bold text-sm text-white">Syndicate Recruits ({syndicateData?.referrals?.length ?? 0})</h4>
                                {syndicateData?.referrals?.length > 0 ? (
                                    <div className="overflow-x-auto">
                                        <table className="w-full text-left text-xs">
                                            <thead>
                                                <tr className="border-b border-[#2e213d] text-gray-400 font-mono text-[10px] uppercase">
                                                    <th className="py-2.5 px-3">Player Name</th>
                                                    <th className="py-2.5 px-3">User Code</th>
                                                    <th className="py-2.5 px-3">VIP Level</th>
                                                    <th className="py-2.5 px-3">Joined Date</th>
                                                </tr>
                                            </thead>
                                            <tbody className="divide-y divide-white/5">
                                                {syndicateData.referrals.map(r => (
                                                    <tr key={r.id}>
                                                        <td className="py-3 px-3 font-bold text-white">{r.name}</td>
                                                        <td className="py-3 px-3 font-mono text-purple-300">{r.user_code}</td>
                                                        <td className="py-3 px-3">Level {r.vip_level || 1}</td>
                                                        <td className="py-3 px-3 text-gray-400 font-mono">{new Date(r.created_at).toLocaleDateString()}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                ) : (
                                    <p className="text-xs text-gray-400 text-center py-6">No recruits in your syndicate yet. Share your referral link above to start earning!</p>
                                )}
                            </div>

                        </div>
                    )}

                    {/* TAB 3: PERSONAL DETAILS & KYC */}
                    {activeTab === 'personal' && (
                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {/* Personal Details Form (2 cols) */}
                            <div className="lg:col-span-2 p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col gap-6">
                                <div>
                                    <h3 className="font-heading font-black text-lg text-white">Residential & Profile Information</h3>
                                    <p className="text-xs text-gray-400">Manage your player profile and registered address details</p>
                                </div>

                                <form onSubmit={handleProfileSubmit} className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold text-gray-300 mb-1.5">First Name</label>
                                            <input 
                                                type="text"
                                                value={profileForm.data.name}
                                                onChange={e => profileForm.setData('name', e.target.value)}
                                                className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                                required
                                            />
                                            {profileForm.errors.name && <p className="text-[10px] text-red-400 mt-1">{profileForm.errors.name}</p>}
                                        </div>

                                        <div>
                                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Last Name / Surname</label>
                                            <input 
                                                type="text"
                                                value={profileForm.data.surname}
                                                onChange={e => profileForm.setData('surname', e.target.value)}
                                                className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                            />
                                            {profileForm.errors.surname && <p className="text-[10px] text-red-400 mt-1">{profileForm.errors.surname}</p>}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Registered Email (Read-only)</label>
                                            <input 
                                                type="email"
                                                value={user?.email || ''}
                                                disabled
                                                className="w-full bg-[#120d1c] border border-[#2b1e38] rounded-xl px-3.5 py-2.5 text-xs text-gray-400 cursor-not-allowed"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Phone Number</label>
                                            <input 
                                                type="text"
                                                value={profileForm.data.phone}
                                                onChange={e => profileForm.setData('phone', e.target.value)}
                                                placeholder="+44 7911 123456"
                                                className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                            />
                                            {profileForm.errors.phone && <p className="text-[10px] text-red-400 mt-1">{profileForm.errors.phone}</p>}
                                        </div>
                                    </div>

                                    <div className="pt-2 border-t border-white/5">
                                        <h4 className="text-xs font-bold text-yellow-400 uppercase tracking-wider mb-3">Residential Address</h4>
                                        <div className="space-y-3">
                                            <div>
                                                <label className="block text-[11px] text-gray-400 mb-1">Street Address, House / Apt Number</label>
                                                <input 
                                                    type="text"
                                                    value={profileForm.data.street}
                                                    onChange={e => profileForm.setData('street', e.target.value)}
                                                    className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                                />
                                            </div>

                                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                                <div>
                                                    <label className="block text-[11px] text-gray-400 mb-1">City</label>
                                                    <input 
                                                        type="text"
                                                        value={profileForm.data.city}
                                                        onChange={e => profileForm.setData('city', e.target.value)}
                                                        className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-[11px] text-gray-400 mb-1">Country</label>
                                                    <input 
                                                        type="text"
                                                        value={user?.country || 'Registered'}
                                                        disabled
                                                        className="w-full bg-[#120d1c] border border-[#2b1e38] rounded-xl px-3.5 py-2.5 text-xs text-gray-400 cursor-not-allowed"
                                                    />
                                                </div>

                                                <div>
                                                    <label className="block text-[11px] text-gray-400 mb-1">Postal / ZIP Code</label>
                                                    <input 
                                                        type="text"
                                                        value={profileForm.data.postcode}
                                                        onChange={e => profileForm.setData('postcode', e.target.value)}
                                                        className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="pt-4 flex justify-end">
                                        <button 
                                            type="submit"
                                            disabled={profileForm.processing}
                                            className="nw-button nw-button-purple px-6 py-2.5 text-xs font-bold"
                                        >
                                            {profileForm.processing ? 'Saving...' : 'Save Profile Changes'}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {/* Verification & KYC Status card */}
                            <div className="p-6 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col justify-between gap-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-3">
                                        <ShieldCheck size={18} className="text-emerald-400" />
                                        <h3 className="font-heading font-black text-base text-white">KYC Verification</h3>
                                    </div>
                                    <p className="text-xs text-gray-300 leading-relaxed mb-4">
                                        Your account is active and verified for social casino gameplay and prize redemptions.
                                    </p>

                                    <div className="space-y-3 text-xs">
                                        <div className="flex items-center justify-between p-3 rounded-xl bg-black/30 border border-white/5">
                                            <span className="text-gray-400">18+ Age Verified</span>
                                            <span className="font-bold text-emerald-400 flex items-center gap-1">
                                                <CheckCircle2 size={13} /> Approved
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between p-3 rounded-xl bg-black/30 border border-white/5">
                                            <span className="text-gray-400">Jurisdiction Check</span>
                                            <span className="font-bold text-emerald-400 flex items-center gap-1">
                                                <CheckCircle2 size={13} /> Eligible ({user?.country || 'OK'})
                                            </span>
                                        </div>

                                        <div className="flex items-center justify-between p-3 rounded-xl bg-black/30 border border-white/5">
                                            <span className="text-gray-400">Terms & Conditions</span>
                                            <span className="font-bold text-purple-300">Accepted</span>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-3.5 rounded-xl bg-[#1b1328] border border-[#3c2a4f] text-[11px] text-gray-400">
                                    Need to update your registered jurisdiction or legal name? Contact our <Link href="/contact" className="text-yellow-400 hover:underline">Support Team</Link>.
                                </div>
                            </div>

                        </div>
                    )}

                    {/* TAB 4: SECURITY & LIMITS */}
                    {activeTab === 'security' && (
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            
                            {/* Password Change Form */}
                            <div className="p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col gap-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <Key size={18} className="text-purple-400" />
                                        <h3 className="font-heading font-black text-lg text-white">Change Password</h3>
                                    </div>
                                    <p className="text-xs text-gray-400">Ensure your account uses a strong, unique password</p>
                                </div>

                                <form onSubmit={handlePasswordSubmit} className="space-y-4">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-300 mb-1.5">Current Password</label>
                                        <input 
                                            type="password"
                                            value={passwordForm.data.current_password}
                                            onChange={e => passwordForm.setData('current_password', e.target.value)}
                                            className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                            required
                                        />
                                        {passwordForm.errors.current_password && <p className="text-[10px] text-red-400 mt-1">{passwordForm.errors.current_password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-300 mb-1.5">New Password (min 8 chars)</label>
                                        <input 
                                            type="password"
                                            value={passwordForm.data.password}
                                            onChange={e => passwordForm.setData('password', e.target.value)}
                                            className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                            required
                                        />
                                        {passwordForm.errors.password && <p className="text-[10px] text-red-400 mt-1">{passwordForm.errors.password}</p>}
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-300 mb-1.5">Confirm New Password</label>
                                        <input 
                                            type="password"
                                            value={passwordForm.data.password_confirmation}
                                            onChange={e => passwordForm.setData('password_confirmation', e.target.value)}
                                            className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-3.5 py-2.5 text-xs text-white focus:outline-none focus:border-purple-500"
                                            required
                                        />
                                    </div>

                                    <div className="pt-3 flex justify-end">
                                        <button 
                                            type="submit"
                                            disabled={passwordForm.processing}
                                            className="nw-button nw-button-purple px-6 py-2.5 text-xs font-bold"
                                        >
                                            {passwordForm.processing ? 'Updating...' : 'Update Password'}
                                        </button>
                                    </div>
                                </form>
                            </div>

                            {/* Responsible Gaming & Self-Exclusion Options */}
                            <div className="p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#342642] flex flex-col justify-between gap-6">
                                <div>
                                    <div className="flex items-center gap-2 mb-2">
                                        <ShieldCheck size={18} className="text-cyan-400" />
                                        <h3 className="font-heading font-black text-lg text-white">Responsible Gaming & Limits</h3>
                                    </div>
                                    <p className="text-xs text-gray-400 mb-5 leading-relaxed">
                                        Neonwin is committed to safe and entertaining gameplay. You can set personal limits or take a break at any time.
                                    </p>

                                    <div className="space-y-3 text-xs">
                                        <div className="p-4 rounded-2xl bg-[#191326] border border-[#362744] flex items-center justify-between">
                                            <div>
                                                <strong className="block text-white text-xs">Play Limits & Session Time</strong>
                                                <span className="text-[11px] text-gray-400">Track and manage time spent in game</span>
                                            </div>
                                            <Link href="/responsible-gaming" className="text-xs font-bold text-cyan-400 hover:text-cyan-300 flex items-center gap-1">
                                                <span>Configure</span>
                                                <ChevronRight size={13} />
                                            </Link>
                                        </div>

                                        <div className="p-4 rounded-2xl bg-[#191326] border border-[#362744] flex items-center justify-between">
                                            <div>
                                                <strong className="block text-white text-xs">Self-Exclusion & Cooling-Off</strong>
                                                <span className="text-[11px] text-gray-400">Temporarily or permanently pause account</span>
                                            </div>
                                            <Link href="/responsible-gaming" className="text-xs font-bold text-pink-400 hover:text-pink-300 flex items-center gap-1">
                                                <span>Details</span>
                                                <ChevronRight size={13} />
                                            </Link>
                                        </div>
                                    </div>
                                </div>

                                <div className="p-4 rounded-2xl bg-amber-950/30 border border-amber-500/30 text-[11px] text-amber-200 leading-relaxed">
                                    ⚠️ <strong>Social Sweeps Notice:</strong> No real money purchase is necessary to play. If you or someone you know has questions about responsible gaming, visit our <Link href="/responsible-gaming" className="underline font-bold text-amber-300">Responsible Gaming</Link> resource page.
                                </div>
                            </div>

                        </div>
                    )}

                </div>
            )}
        </MainLayout>
    );
}
