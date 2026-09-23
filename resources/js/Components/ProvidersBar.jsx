import React from 'react';
import { ArrowUpRight, Sparkles } from 'lucide-react';
import ProviderLogo from './ProviderLogo';

const fallbackProviders = [
    { code: 'PRAGMATIC', name: 'Pragmatic Play' },
    { code: 'PP_LIVE_PRO', name: 'Pragmatic Live' },
    { code: 'PGSOFT', name: 'PG Soft' },
    { code: 'HACKSAW', name: 'Hacksaw Gaming' },
    { code: 'SPRIBE', name: 'Spribe' },
    { code: 'HABANERO', name: 'Habanero' },
    { code: 'BOOONGO', name: 'Booongo' },
    { code: 'PLAYSON', name: 'Playson' },
    { code: 'CQ9', name: 'CQ9 Gaming' },
    { code: 'EVOPLAY', name: 'Evoplay' },
    { code: 'NOLIMIT', name: 'NoLimit City' },
    { code: 'EVOLUTION', name: 'Evolution' },
    { code: 'NETENT', name: 'NetEnt' },
    { code: 'FASTSPIN', name: 'FastSpin' },
];

export default function ProvidersBar({ providers = [], onSelectProvider }) {
    const displayProviders = providers.length >= 6 ? providers : fallbackProviders;

    return (
        <section className="nw-providers pt-8 border-t border-[#291e36]" aria-label="Game providers">
            <div className="flex items-center justify-between mb-5 flex-wrap gap-2">
                <div className="flex items-center gap-2">
                    <span className="w-2 h-2 rounded-full bg-purple-400 shadow-[0_0_8px_rgba(192,132,252,0.8)] animate-pulse" />
                    <span className="text-[10px] font-black uppercase tracking-widest text-[#a899bc]">
                        GREAT GAMES. GREAT CREATORS.
                    </span>
                </div>
                <span className="text-[11px] text-gray-400 font-mono">
                    {displayProviders.length} official providers & studios
                </span>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-7 gap-3">
                {displayProviders.map(provider => (
                    <button
                        key={provider.code}
                        onClick={() => onSelectProvider && onSelectProvider(provider.code)}
                        className="group relative px-4 py-3.5 rounded-2xl bg-[#130d1f]/90 hover:bg-[#201435] border border-[#2f2040] hover:border-purple-500/50 hover:shadow-[0_0_25px_rgba(168,85,247,0.25)] flex items-center justify-center transition-all duration-300 hover:-translate-y-0.5 text-gray-400 hover:text-white overflow-hidden"
                        title={`Explore games by ${provider.name}`}
                    >
                        {/* Subtle background glow effect on hover */}
                        <div className="absolute inset-0 bg-gradient-to-r from-purple-500/0 via-purple-500/5 to-pink-500/0 opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none" />

                        <div className="relative z-10 flex items-center justify-center w-full min-h-[32px]">
                            <ProviderLogo 
                                code={provider.code} 
                                name={provider.name} 
                                className="h-6 max-w-[130px] w-auto transition-transform duration-300 group-hover:scale-105 filter drop-shadow-sm" 
                            />
                        </div>

                        <ArrowUpRight 
                            size={12} 
                            className="absolute top-2 right-2 text-gray-600 group-hover:text-purple-300 opacity-0 group-hover:opacity-100 transition-all duration-200" 
                        />
                    </button>
                ))}
            </div>
        </section>
    );
}
