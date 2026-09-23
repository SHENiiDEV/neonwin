import React, { useState, useEffect } from 'react';
import { Link } from '@inertiajs/react';
import { Crosshair, Trophy, Flame, Zap, ArrowRight, Play, Sparkles, Clock, CheckCircle2 } from 'lucide-react';
import axios from 'axios';

export default function ChallengesSection() {
    const [challenges, setChallenges] = useState([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        axios.get('/api/challenges')
            .then(res => setChallenges(res.data.challenges || []))
            .catch(err => console.error(err))
            .finally(() => setLoading(false));
    }, []);

    if (challenges.length === 0 && !loading) return null;

    return (
        <section className="w-full flex flex-col gap-4 my-2" aria-labelledby="challenges-heading">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2.5">
                    <div className="w-8 h-8 rounded-xl bg-pink-950/60 border border-pink-500/30 flex items-center justify-center text-pink-400">
                        <Crosshair size={18} />
                    </div>
                    <div>
                        <h2 id="challenges-heading" className="font-heading font-black text-lg sm:text-xl text-white flex items-center gap-2">
                            <span>Multiplier Hunts & Live Bounties</span>
                            <span className="px-2 py-0.5 rounded-full bg-pink-500/20 text-pink-300 text-[10px] uppercase font-mono font-bold animate-pulse">
                                LIVE
                            </span>
                        </h2>
                        <p className="text-xs text-gray-400">Be the first player to hit the target multiplier and claim the bounty pool!</p>
                    </div>
                </div>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                {challenges.map((c) => {
                    const isCompleted = c.status === 'completed';
                    return (
                        <div 
                            key={c.id}
                            className={`p-5 rounded-2xl border transition relative overflow-hidden flex flex-col justify-between ${
                                isCompleted 
                                    ? 'bg-[#15101f] border-gray-800 opacity-70' 
                                    : 'bg-gradient-to-b from-[#1f1530] to-[#120d1e] border-pink-500/40 hover:border-pink-400 shadow-[0_0_20px_rgba(236,72,153,0.15)]'
                            }`}
                        >
                            <div className="flex items-start justify-between gap-2 mb-3">
                                <div>
                                    <span className="text-[10px] text-pink-400 font-mono font-bold uppercase tracking-wider block">
                                        TARGET: {Number(c.target_multiplier).toFixed(0)}x MULTIPLIER
                                    </span>
                                    <h3 className="font-heading font-black text-base text-white mt-0.5">
                                        {c.title}
                                    </h3>
                                    <span className="text-xs text-gray-400">{c.game_name} • Min Bet: {Math.floor(Number(c.min_bet)).toLocaleString()} Coins</span>
                                </div>

                                <div className="p-2.5 rounded-xl bg-yellow-400/10 border border-yellow-400/30 text-center flex-shrink-0">
                                    <span className="text-[9px] font-bold text-yellow-400 uppercase block">Prize Pool</span>
                                    <strong className="font-heading font-black text-base text-yellow-300">
                                        {Math.floor(Number(c.prize_sc)).toLocaleString()} <small className="text-[10px]">Coins</small>
                                    </strong>
                                </div>
                            </div>

                            <div className="pt-3 border-t border-white/5 flex items-center justify-between">
                                {isCompleted ? (
                                    <span className="text-xs text-emerald-400 font-bold flex items-center gap-1.5">
                                        <CheckCircle2 size={14} /> Won by {c.winner_name || 'Player'} ({c.winner_multiplier}x)
                                    </span>
                                ) : (
                                    <>
                                        <span className="text-[11px] text-gray-400 flex items-center gap-1">
                                            <Clock size={12} className="text-pink-400" /> Active Bounty
                                        </span>
                                        <Link 
                                            href={`/game/${c.game_code}`}
                                            className="px-3.5 py-1.5 rounded-xl bg-pink-600 hover:bg-pink-500 text-white font-heading font-black text-xs uppercase tracking-wider flex items-center gap-1.5 transition shadow-sm"
                                        >
                                            <Play size={11} fill="currentColor" /> Play & Hunt
                                        </Link>
                                    </>
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>
        </section>
    );
}
