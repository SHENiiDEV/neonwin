import React, { useState, useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';
import { X, Box, Sparkles, Trophy, Coins, Zap, Check, ArrowRight, LoaderCircle, ShieldAlert, Award } from 'lucide-react';
import axios from 'axios';
import { playCrateUnlockSound, playCoinSound, playReelTickSound, playEpicFanfareSound } from '../../utils/soundEffects';

const tierColors = {
    bronze: { border: 'border-amber-700/70', text: 'text-amber-400', bg: 'bg-amber-950/50', glow: 'shadow-[0_0_20px_rgba(180,83,9,0.35)]', badge: 'bg-amber-900/60 text-amber-300' },
    silver: { border: 'border-slate-400/70', text: 'text-slate-300', bg: 'bg-slate-900/50', glow: 'shadow-[0_0_20px_rgba(148,163,184,0.35)]', badge: 'bg-slate-800/60 text-slate-300' },
    gold: { border: 'border-yellow-500/70', text: 'text-yellow-400', bg: 'bg-yellow-950/50', glow: 'shadow-[0_0_25px_rgba(234,179,8,0.4)]', badge: 'bg-yellow-900/60 text-yellow-300' },
    platinum: { border: 'border-cyan-500/70', text: 'text-cyan-400', bg: 'bg-cyan-950/50', glow: 'shadow-[0_0_25px_rgba(6,182,212,0.4)]', badge: 'bg-cyan-900/60 text-cyan-300' },
    mythic: { border: 'border-purple-500/90', text: 'text-purple-400', bg: 'bg-purple-950/60', glow: 'shadow-[0_0_35px_rgba(168,85,247,0.5)]', badge: 'bg-purple-900/60 text-purple-300' },
};

const tierDetails = {
    bronze: { label: 'Bronze', maxCoins: '1,000,000', range: '200K – 1M Coins', xp: 'Up to 250 XP', emoji: '🥉' },
    silver: { label: 'Silver', maxCoins: '2,000,000', range: '500K – 2M Coins', xp: 'Up to 500 XP', emoji: '🥈' },
    gold: { label: 'Gold', maxCoins: '5,000,000', range: '1M – 5M Coins', xp: 'Up to 1,000 XP', emoji: '🥇' },
    platinum: { label: 'Platinum', maxCoins: '10,000,000', range: '2M – 10M Coins', xp: 'Up to 2,000 XP', emoji: '💠' },
    mythic: { label: 'Mythic', maxCoins: '50,000,000', range: '5M – 50M Coins', xp: 'Up to 5,000 XP', emoji: '💎' },
};

const possiblePrizesPool = [
    { tier: 'bronze', coins: 200000, xp: 50, emoji: '🥉' },
    { tier: 'bronze', coins: 300000, xp: 100, emoji: '🥉' },
    { tier: 'bronze', coins: 500000, xp: 150, emoji: '🥉' },
    { tier: 'silver', coins: 800000, xp: 200, emoji: '🥈' },
    { tier: 'silver', coins: 1200000, xp: 350, emoji: '🥈' },
    { tier: 'silver', coins: 2000000, xp: 500, emoji: '🥈' },
    { tier: 'gold', coins: 1500000, xp: 300, emoji: '🥇' },
    { tier: 'gold', coins: 2500000, xp: 600, emoji: '🥇' },
    { tier: 'gold', coins: 5000000, xp: 1000, emoji: '🥇' },
    { tier: 'platinum', coins: 3500000, xp: 800, emoji: '💠' },
    { tier: 'platinum', coins: 5000000, xp: 1200, emoji: '💠' },
    { tier: 'platinum', coins: 10000000, xp: 2000, emoji: '💠' },
    { tier: 'mythic', coins: 7500000, xp: 1500, emoji: '💎' },
    { tier: 'mythic', coins: 25000000, xp: 3000, emoji: '💎' },
    { tier: 'mythic', coins: 50000000, xp: 5000, emoji: '💎' },
];

export default function CratesModal({ isOpen, onClose }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const soundEnabled = user?.sound_enabled !== false;

    const [crates, setCrates] = useState([]);
    const [loading, setLoading] = useState(true);
    const [openingCrate, setOpeningCrate] = useState(null);
    const [revealedPrize, setRevealedPrize] = useState(null);
    const [animationState, setAnimationState] = useState('idle'); // 'idle' | 'spinning' | 'revealed'
    
    // Roulette Strip State
    const [reelItems, setReelItems] = useState([]);
    const [reelTransform, setReelTransform] = useState('translateX(0px)');
    const [isReelTransitioning, setIsReelTransitioning] = useState(false);
    const reelContainerRef = useRef(null);
    const tickIntervalsRef = useRef([]);

    const fetchCrates = async () => {
        if (!user) return;
        setLoading(true);
        try {
            const res = await axios.get('/api/crates');
            setCrates(res.data.crates || []);
        } catch (e) {
            console.error(e);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (isOpen && user) {
            fetchCrates();
            setRevealedPrize(null);
            setOpeningCrate(null);
            setAnimationState('idle');
            setReelTransform('translateX(0px)');
            setIsReelTransitioning(false);
        }
        return () => {
            tickIntervalsRef.current.forEach(clearTimeout);
        };
    }, [isOpen]);

    if (!isOpen) return null;

    const generateReel = (winningPrize, crateTier) => {
        const totalItems = 40;
        const targetIndex = 30; // The landing slot
        const items = [];

        for (let i = 0; i < totalItems; i++) {
            if (i === targetIndex) {
                items.push({
                    id: `win-${i}`,
                    tier: crateTier,
                    sc: winningPrize.reward_sc,
                    xp: winningPrize.reward_xp,
                    emoji: tierDetails[crateTier]?.emoji || '🎁',
                    isWinner: true,
                });
            } else {
                const randomPrize = possiblePrizesPool[Math.floor(Math.random() * possiblePrizesPool.length)];
                items.push({
                    id: `item-${i}`,
                    ...randomPrize,
                    isWinner: false,
                });
            }
        }
        return { items, targetIndex };
    };

    const scheduleReelAudioTicks = () => {
        tickIntervalsRef.current.forEach(clearTimeout);
        tickIntervalsRef.current = [];

        // Decelerating tick series over 4.5 seconds
        let delay = 60;
        let currentTime = 0;
        const totalDuration = 4400;

        while (currentTime < totalDuration) {
            const timeToFire = currentTime;
            const timer = setTimeout(() => {
                playReelTickSound(soundEnabled);
            }, timeToFire);
            tickIntervalsRef.current.push(timer);

            // Increase step interval progressively
            const progress = currentTime / totalDuration;
            delay = 45 + Math.pow(progress, 3) * 450;
            currentTime += delay;
        }
    };

    const handleOpenCrate = async (crate) => {
        if (animationState !== 'idle' || crate.status === 'opened') return;
        
        setOpeningCrate(crate);
        setAnimationState('spinning');
        setIsReelTransitioning(false);
        setReelTransform('translateX(0px)');
        playCrateUnlockSound(soundEnabled);

        try {
            const res = await axios.post(`/api/crates/${crate.id}/open`);
            const rewardData = res.data;

            // Generate the reel with the winning item at index 30
            const { items, targetIndex } = generateReel(rewardData, crate.tier);
            setReelItems(items);

            // Wait a brief tick for DOM to render the strip
            setTimeout(() => {
                const cardWidth = 140; // 128px card + 12px gap
                const containerWidth = reelContainerRef.current ? reelContainerRef.current.offsetWidth : 600;
                
                // Slight random offset inside winning card for organic realism (-25px to +25px)
                const jitter = (Math.random() - 0.5) * 40;
                const winningCenter = targetIndex * cardWidth + (cardWidth / 2);
                const finalTranslate = -1 * (winningCenter - (containerWidth / 2) + jitter);

                setIsReelTransitioning(true);
                setReelTransform(`translateX(${finalTranslate}px)`);

                // Start decelerating sound ticks
                scheduleReelAudioTicks();

                // 4.6s animation finishes
                setTimeout(() => {
                    playEpicFanfareSound(soundEnabled);
                    setTimeout(() => {
                        setRevealedPrize({
                            sc: rewardData.reward_sc,
                            xp: rewardData.reward_xp,
                            name: crate.name,
                            tier: crate.tier,
                        });
                        setAnimationState('revealed');
                        router.reload({ only: ['auth'] });
                        fetchCrates();
                    }, 500);
                }, 4600);
            }, 100);

        } catch (e) {
            console.error(e);
            setAnimationState('idle');
            setOpeningCrate(null);
        }
    };

    const pendingCrates = crates.filter(c => c.status === 'pending');
    const openedCrates = crates.filter(c => c.status === 'opened');

    return (
        <div className="nw-topup-backdrop" onClick={e => e.target === e.currentTarget && animationState !== 'spinning' && onClose()}>
            <section className="relative w-full max-w-4xl rounded-3xl bg-[#130e21] border border-[#3f2c53] p-6 sm:p-8 shadow-[0_25px_90px_rgba(0,0,0,0.95)] overflow-hidden text-white animate-in zoom-in-95 duration-200">
                <button 
                    onClick={onClose} 
                    disabled={animationState === 'spinning'}
                    className="absolute top-5 right-5 p-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition disabled:opacity-30"
                >
                    <X size={18} />
                </button>

                {/* Header */}
                <div className="flex items-center gap-3.5 mb-6">
                    <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-purple-600 via-pink-600 to-amber-500 flex items-center justify-center text-white shadow-[0_0_25px_rgba(168,85,247,0.4)]">
                        <Box size={24} />
                    </div>
                    <div>
                        <div className="flex items-center gap-2">
                            <h2 className="font-heading font-black text-xl sm:text-2xl text-white tracking-wide">Cyber Crates</h2>
                            <span className="px-2.5 py-0.5 rounded-full bg-yellow-400/20 border border-yellow-400/40 text-yellow-300 text-[11px] font-black uppercase tracking-wider">
                                {pendingCrates.length} Ready
                            </span>
                        </div>
                        <p className="text-xs text-gray-400">Unlock mystery Sweeps Coins & VIP XP with high-velocity Cyberpunk Reel unboxing</p>
                    </div>
                </div>

                {/* CYBER ROULETTE REEL ARENA */}
                {animationState === 'spinning' && (
                    <div className="my-6 p-6 sm:p-8 rounded-3xl bg-black/80 border border-purple-500/40 relative shadow-[inset_0_0_50px_rgba(0,0,0,0.9)] overflow-hidden">
                        
                        {/* Title Bar */}
                        <div className="flex items-center justify-between mb-4">
                            <div className="flex items-center gap-2">
                                <span className="w-2.5 h-2.5 rounded-full bg-yellow-400 animate-ping" />
                                <span className="font-heading font-black text-xs text-yellow-300 uppercase tracking-widest">
                                    Opening: {openingCrate?.name || 'Cyber Crate'}
                                </span>
                            </div>
                            <span className="text-[10px] font-mono text-purple-300 uppercase tracking-wider">
                                Decrypting Seed...
                            </span>
                        </div>

                        {/* Reel Viewport Window */}
                        <div 
                            ref={reelContainerRef}
                            className="relative w-full h-44 overflow-hidden rounded-2xl bg-[#0a0712] border border-[#2d1b40]"
                            style={{
                                maskImage: 'linear-gradient(to right, transparent, black 15%, black 85%, transparent)',
                                WebkitMaskImage: 'linear-gradient(to right, transparent, black 15%, black 85%, transparent)'
                            }}
                        >
                            {/* Center Selector Laser Needle (Top & Bottom Pointers) */}
                            <div className="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-1 bg-yellow-400 z-30 shadow-[0_0_15px_rgba(250,204,21,1)] pointer-events-none flex flex-col justify-between items-center">
                                <div className="text-yellow-300 text-sm -mt-1 font-black drop-shadow-[0_0_8px_rgba(250,204,21,1)]">▼</div>
                                <div className="text-yellow-300 text-sm -mb-1 font-black drop-shadow-[0_0_8px_rgba(250,204,21,1)]">▲</div>
                            </div>

                            {/* Center Ambient Glow Column */}
                            <div className="absolute inset-y-0 left-1/2 -translate-x-1/2 w-32 bg-yellow-400/10 pointer-events-none z-10 blur-xl" />

                            {/* Scrolling Items Strip */}
                            <div 
                                className="flex items-center gap-3 absolute top-0 bottom-0 left-0 h-full py-3.5 px-4"
                                style={{
                                    transform: reelTransform,
                                    transition: isReelTransitioning ? 'transform 4.5s cubic-bezier(0.12, 0.85, 0.22, 1)' : 'none',
                                    willChange: 'transform'
                                }}
                            >
                                {reelItems.map((item) => {
                                    const style = tierColors[item.tier] || tierColors.bronze;
                                    return (
                                        <div 
                                            key={item.id}
                                            className={`w-32 h-full flex-shrink-0 rounded-2xl border-2 ${style.border} ${style.bg} flex flex-col items-center justify-between p-3 relative shadow-md transition`}
                                        >
                                            <span className={`text-[9px] font-black uppercase tracking-wider ${style.text}`}>
                                                {item.tier}
                                            </span>
                                            <div className="text-3xl filter drop-shadow-md">
                                                {item.emoji}
                                            </div>
                                            <div className="text-center font-heading font-black leading-tight">
                                                <div className="text-sm text-yellow-300">+{item.coins >= 1000000 ? `${item.coins / 1000000}M` : `${item.coins / 1000}K`} Coins</div>
                                                <div className="text-[9px] text-purple-300 font-mono">+{item.xp} XP</div>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Bottom Status Ticker */}
                        <div className="flex items-center justify-center gap-2 mt-4 text-[11px] text-gray-400 font-mono">
                            <Zap size={13} className="text-yellow-400 animate-bounce" />
                            <span>Lootbox deceleration sequence engaged</span>
                        </div>
                    </div>
                )}

                {/* WINNER REVEAL POPUP ARENA */}
                {animationState === 'revealed' && revealedPrize && (
                    <div className="my-6 p-8 sm:p-12 rounded-3xl bg-gradient-to-b from-[#281647] via-[#1b0e33] to-[#0f0a1c] border-2 border-yellow-400/80 shadow-[0_0_80px_rgba(234,179,8,0.4)] flex flex-col items-center justify-center text-center gap-6 animate-in zoom-in-95 duration-300">
                        <div className="w-24 h-24 rounded-3xl bg-yellow-400/20 border-2 border-yellow-400 flex items-center justify-center text-5xl shadow-[0_0_40px_rgba(250,204,21,0.6)] animate-bounce">
                            {tierDetails[revealedPrize.tier]?.emoji || '🎁'}
                        </div>

                        <div className="space-y-2">
                            <span className="px-3.5 py-1 rounded-full bg-yellow-400/20 border border-yellow-400/40 text-yellow-300 text-xs font-black uppercase tracking-widest">
                                {revealedPrize.tier} Crate Decrypted!
                            </span>
                            <h3 className="font-heading font-black text-4xl sm:text-5xl text-white mt-2 drop-shadow-[0_0_20px_rgba(255,255,255,0.4)]">
                                +{Number(revealedPrize.sc || 0).toLocaleString('en-US')} <span className="text-yellow-300">Coins</span>
                            </h3>
                            <div className="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-purple-900/70 border border-purple-500/50 text-sm font-bold text-purple-200 shadow-md">
                                <Sparkles size={14} className="text-yellow-400" />
                                <span>+{revealedPrize.xp} VIP XP</span>
                            </div>
                        </div>

                        <button
                            onClick={() => {
                                setRevealedPrize(null);
                                setAnimationState('idle');
                                setOpeningCrate(null);
                            }}
                            className="nw-button nw-button-purple px-10 py-3.5 text-sm font-heading font-black uppercase tracking-wider shadow-2xl hover:scale-105 transition"
                        >
                            Claim & Continue
                        </button>
                    </div>
                )}

                {/* CRATES INVENTORY GRID */}
                {animationState === 'idle' && (
                    <div className="space-y-6">
                        {loading ? (
                            <div className="p-12 text-center text-gray-400 flex flex-col items-center gap-2">
                                <LoaderCircle size={28} className="animate-spin text-purple-400" />
                                <span className="text-xs">Loading crates inventory...</span>
                            </div>
                        ) : pendingCrates.length > 0 ? (
                            <div>
                                <h3 className="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3 flex items-center justify-between">
                                    <span>Available Crates to Open ({pendingCrates.length})</span>
                                    <span className="text-[11px] text-yellow-400 font-mono">Instant Coins & VIP XP</span>
                                </h3>
                                
                                <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                                    {pendingCrates.map((crate) => {
                                        const style = tierColors[crate.tier] || tierColors.bronze;
                                        const info = tierDetails[crate.tier] || tierDetails.bronze;
                                        return (
                                            <div 
                                                key={crate.id}
                                                className={`p-5 rounded-2xl border-2 ${style.border} ${style.bg} ${style.glow} flex flex-col justify-between gap-4 transition group hover:scale-[1.02]`}
                                            >
                                                <div className="flex items-center justify-between">
                                                    <span className={`text-[10px] font-black uppercase tracking-wider ${style.text}`}>
                                                        {crate.tier} Tier
                                                    </span>
                                                    <Sparkles size={14} className={style.text} />
                                                </div>

                                                <div className="flex flex-col items-center text-center my-2">
                                                    <div className="w-16 h-16 rounded-2xl bg-black/40 border border-white/10 flex items-center justify-center text-3xl mb-2 group-hover:rotate-6 group-hover:scale-110 transition duration-300">
                                                        {info.emoji}
                                                    </div>
                                                    <strong className="font-heading font-black text-sm text-white line-clamp-1">
                                                        {crate.name}
                                                    </strong>
                                                    <div className="flex flex-col items-center mt-1">
                                                        <span className="text-xs font-bold text-yellow-300">
                                                            Win up to <strong className="text-white font-black">{info.maxCoins} Coins</strong>
                                                        </span>
                                                        <span className="text-[10px] text-gray-400 font-mono mt-0.5">
                                                            Drops {info.range}
                                                        </span>
                                                    </div>
                                                </div>

                                                <button
                                                    onClick={() => handleOpenCrate(crate)}
                                                    className="w-full py-3 rounded-xl bg-gradient-to-r from-yellow-400 via-amber-400 to-yellow-500 hover:from-yellow-300 hover:to-amber-300 text-black font-heading font-black text-xs uppercase tracking-wider shadow-lg transition active:scale-95 flex items-center justify-center gap-1.5"
                                                >
                                                    <Zap size={14} fill="currentColor" />
                                                    <span>Open Crate</span>
                                                </button>
                                            </div>
                                        );
                                    })}
                                </div>
                            </div>
                        ) : (
                            <div className="p-10 rounded-2xl bg-[#171224] border border-[#382649] text-center flex flex-col items-center gap-3">
                                <Box size={40} className="text-gray-600" />
                                <h3 className="font-heading font-bold text-white text-sm">No Pending Crates in Inventory</h3>
                                <p className="text-xs text-gray-400 max-w-md">
                                    Play games to wager and climb VIP ranks! Every new VIP Level and every wager milestone awards a brand new Cyber Crate.
                                </p>
                            </div>
                        )}

                        {openedCrates.length > 0 && (
                            <div className="pt-2">
                                <h3 className="text-xs font-bold uppercase tracking-wider text-gray-500 mb-2.5">
                                    Recent Unboxing History ({openedCrates.length})
                                </h3>
                                <div className="space-y-2 max-h-40 overflow-y-auto no-scrollbar">
                                    {openedCrates.map(c => (
                                        <div key={c.id} className="p-3 rounded-xl bg-white/[0.02] border border-white/5 flex items-center justify-between text-xs hover:bg-white/[0.04] transition">
                                            <div className="flex items-center gap-2.5">
                                                <Check size={14} className="text-emerald-400" />
                                                <span className="font-bold text-gray-300">{c.name}</span>
                                            </div>
                                            <div className="flex items-center gap-2 font-mono">
                                                <span className="text-yellow-300 font-bold">+{Number(c.reward_sc || 0).toLocaleString('en-US')} Coins</span>
                                                <span className="text-purple-400 text-[10px]">+{c.reward_xp} XP</span>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </div>
                )}
            </section>
        </div>
    );
}
