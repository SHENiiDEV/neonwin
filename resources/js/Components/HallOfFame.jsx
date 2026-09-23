import React from 'react';
import { Trophy, Flame, Zap, Sparkles, TrendingUp } from 'lucide-react';

const mockBigWins = [
    { id: 1, player: 'NeonMaster', game: 'Sweet Bonanza', multiplier: '2,150x', win: '4,300.00 SC', avatar: '👑', time: '12m ago' },
    { id: 2, player: 'CyberViper', game: 'Sugar Rush', multiplier: '1,540x', win: '1,540.00 SC', avatar: '⚡', time: '28m ago' },
    { id: 3, player: 'LuckyRonin', game: 'Buffalo King', multiplier: '890x', win: '1,780.00 SC', avatar: '🎯', time: '45m ago' },
    { id: 4, player: 'StarGazer', game: 'Sweet Bonanza Xmas', multiplier: '620x', win: '1,240.00 SC', avatar: '💎', time: '1h ago' },
];


export default function HallOfFame({ wins = mockBigWins }) {
    return (
        <section className="w-full flex flex-col gap-3 my-2" aria-labelledby="hof-heading">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-2">
                    <Trophy size={18} className="text-yellow-400" />
                    <h2 id="hof-heading" className="font-heading font-black text-lg sm:text-xl text-white">
                        Hall of Fame • Today’s Mega Wins
                    </h2>
                </div>
                <span className="text-[10px] font-mono text-yellow-400/90 flex items-center gap-1">
                    <Sparkles size={11} /> Live Community Feed
                </span>
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                {wins.map((w) => (
                    <div 
                        key={w.id}
                        className="p-4 rounded-2xl bg-gradient-to-br from-[#1b1328] to-[#120d1c] border border-yellow-500/30 hover:border-yellow-400/60 shadow-[0_0_20px_rgba(234,179,8,0.1)] transition flex items-center justify-between group"
                    >
                        <div className="flex items-center gap-3">
                            <span className="w-10 h-10 rounded-xl bg-yellow-950/60 border border-yellow-500/40 flex items-center justify-center text-xl group-hover:scale-110 transition">
                                {w.avatar}
                            </span>
                            <div>
                                <div className="flex items-center gap-1.5">
                                    <strong className="text-xs text-white font-heading">{w.player}</strong>
                                    <span className="text-[9px] text-gray-500 font-mono">• {w.time}</span>
                                </div>
                                <span className="text-[11px] text-gray-400">{w.game}</span>
                            </div>
                        </div>

                        <div className="text-right">
                            <span className="px-2 py-0.5 rounded bg-yellow-400/20 text-yellow-300 font-mono font-bold text-[10px] block mb-0.5">
                                {w.multiplier}
                            </span>
                            <span className="font-heading font-black text-xs text-emerald-400">
                                {w.win}
                            </span>
                        </div>
                    </div>
                ))}
            </div>
        </section>
    );
}
