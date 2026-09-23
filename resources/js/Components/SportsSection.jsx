import React, { useState } from 'react';
import { Swords, Activity, Trophy, Shield, Zap, Sparkles } from 'lucide-react';

export default function SportsSection({ onOpenDeposit }) {
    const [selectedSport, setSelectedSport] = useState('football');

    const matches = [
        {
            id: 1,
            sport: 'football',
            league: 'UEFA Champions League',
            time: 'LIVE • 64\'',
            isLive: true,
            team1: { name: 'Real Madrid', score: 2, icon: '👑' },
            team2: { name: 'Manchester City', score: 1, icon: '🦅' },
            odds: { home: 1.85, draw: 3.40, away: 4.10 }
        },
        {
            id: 2,
            sport: 'football',
            league: 'Premier League',
            time: 'Today • 20:45',
            isLive: false,
            team1: { name: 'Arsenal', score: '-', icon: '🔴' },
            team2: { name: 'Chelsea', score: '-', icon: '🔵' },
            odds: { home: 1.65, draw: 3.90, away: 5.20 }
        },
        {
            id: 3,
            sport: 'esports',
            league: 'CS:GO Major Championship',
            time: 'LIVE • Map 2',
            isLive: true,
            team1: { name: 'FaZe Clan', score: 14, icon: '🔥' },
            team2: { name: 'NAVI Cyber', score: 12, icon: '⚡' },
            odds: { home: 1.55, draw: null, away: 2.35 }
        },
        {
            id: 4,
            sport: 'esports',
            league: 'Dota 2 The International',
            time: 'Today • 22:00',
            isLive: false,
            team1: { name: 'Team Spirit', score: '-', icon: '🐉' },
            team2: { name: 'OG Esports', score: '-', icon: '🌻' },
            odds: { home: 1.72, draw: null, away: 2.10 }
        }
    ];

    const filteredMatches = matches.filter(m => selectedSport === 'all' || m.sport === selectedSport);

    return (
        <div className="w-full my-8 space-y-4">
            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-[#1E2638] pb-3">
                <div className="flex items-center gap-2.5">
                    <div className="w-2.5 h-6 rounded-full bg-gradient-to-b from-blue-500 to-cyan-500 shadow-neon-blue"></div>
                    <h2 className="font-heading font-black text-lg md:text-xl text-white tracking-wide uppercase flex items-center gap-2">
                        <span>LIVE SPORTS & ESPORTS</span>
                    </h2>
                </div>

                <div className="flex items-center bg-[#131826] p-1 rounded-xl border border-white/5">
                    <button
                        onClick={() => setSelectedSport('football')}
                        className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition ${selectedSport === 'football' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-400 hover:text-white'}`}
                    >
                        ⚽ FOOTBALL
                    </button>
                    <button
                        onClick={() => setSelectedSport('esports')}
                        className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition ${selectedSport === 'esports' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-400 hover:text-white'}`}
                    >
                        🎮 ESPORTS
                    </button>
                    <button
                        onClick={() => setSelectedSport('all')}
                        className={`px-3.5 py-1.5 rounded-lg text-xs font-bold transition ${selectedSport === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'text-gray-400 hover:text-white'}`}
                    >
                        ALL MATCHES
                    </button>
                </div>
            </div>

            {/* Matches Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {filteredMatches.map((m) => (
                    <div 
                        key={m.id}
                        className="rounded-2xl bg-[#111624] border border-[#1E2638] hover:border-blue-500/40 p-4 transition-all duration-300 shadow-md"
                    >
                        {/* League & Live Status */}
                        <div className="flex items-center justify-between text-xs text-gray-400 mb-3">
                            <span className="font-semibold text-gray-300">{m.league}</span>
                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-black uppercase ${m.isLive ? 'bg-red-950/80 border border-red-500/40 text-red-400 animate-pulse' : 'bg-white/5 text-gray-400'}`}>
                                {m.time}
                            </span>
                        </div>

                        {/* Teams */}
                        <div className="space-y-2 mb-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 font-bold text-white text-sm">
                                    <span>{m.team1.icon}</span>
                                    <span>{m.team1.name}</span>
                                </div>
                                <span className="font-mono font-black text-base text-yellow-400">{m.team1.score}</span>
                            </div>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2 font-bold text-white text-sm">
                                    <span>{m.team2.icon}</span>
                                    <span>{m.team2.name}</span>
                                </div>
                                <span className="font-mono font-black text-base text-yellow-400">{m.team2.score}</span>
                            </div>
                        </div>

                        {/* Odds Buttons */}
                        <div className="grid grid-cols-3 gap-2">
                            <button 
                                onClick={onOpenDeposit}
                                className="py-2 px-2 rounded-xl bg-[#182033] hover:bg-blue-600 text-gray-300 hover:text-white font-mono font-bold text-xs flex flex-col items-center border border-white/5 hover:border-blue-500 transition group"
                            >
                                <span className="text-[10px] text-gray-400 group-hover:text-blue-200">1</span>
                                <span>{m.odds.home}</span>
                            </button>

                            {m.odds.draw ? (
                                <button 
                                    onClick={onOpenDeposit}
                                    className="py-2 px-2 rounded-xl bg-[#182033] hover:bg-blue-600 text-gray-300 hover:text-white font-mono font-bold text-xs flex flex-col items-center border border-white/5 hover:border-blue-500 transition group"
                                >
                                    <span className="text-[10px] text-gray-400 group-hover:text-blue-200">X</span>
                                    <span>{m.odds.draw}</span>
                                </button>
                            ) : (
                                <div className="py-2 px-2 rounded-xl bg-black/20 flex items-center justify-center text-gray-600 text-[10px] font-mono">-</div>
                            )}

                            <button 
                                onClick={onOpenDeposit}
                                className="py-2 px-2 rounded-xl bg-[#182033] hover:bg-blue-600 text-gray-300 hover:text-white font-mono font-bold text-xs flex flex-col items-center border border-white/5 hover:border-blue-500 transition group"
                            >
                                <span className="text-[10px] text-gray-400 group-hover:text-blue-200">2</span>
                                <span>{m.odds.away}</span>
                            </button>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
