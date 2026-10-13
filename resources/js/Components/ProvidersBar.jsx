import React from 'react';
import { ArrowUpRight } from 'lucide-react';
import ProviderLogo from './ProviderLogo';

export default function ProvidersBar({ providers = [], onSelectProvider }) {
    if (providers.length === 0) {
        return null;
    }

    return (
        <section className="nw-providers border-t border-[#291e36] pt-8" aria-labelledby="studio-heading">
            <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <p className="mb-2 text-[10px] font-bold uppercase tracking-[0.28em] text-[#b393d7]">The studios behind the action</p>
                    <h2 id="studio-heading" className="text-2xl font-black tracking-tight text-white sm:text-3xl">GREAT GAMES. <span className="text-[#d7aaef]">GREAT CREATORS.</span></h2>
                </div>
                <p className="text-xs text-[#9d90ab]">{providers.length} studios · sorted by available games</p>
            </div>

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5">
                {providers.map((provider, index) => (
                    <button
                        key={provider.code}
                        type="button"
                        onClick={() => onSelectProvider?.(provider.code)}
                        className="group relative flex min-h-[104px] flex-col justify-between overflow-hidden rounded-2xl border border-[#382844] bg-gradient-to-br from-[#21152b] to-[#120e19] p-4 text-left transition duration-200 hover:-translate-y-0.5 hover:border-[#9b5acd] hover:shadow-[0_12px_35px_rgba(104,40,146,0.18)] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-purple-400"
                        aria-label={`Show ${provider.games_count} games by ${provider.name}`}
                    >
                        <div className="flex min-w-0 items-center gap-3 pr-4">
                            <ProviderLogo provider={provider} />
                            <span className="min-w-0 break-words text-sm font-bold leading-tight text-white">{provider.name}</span>
                        </div>
                        <div className="mt-4 flex items-center justify-between text-[11px] text-[#ad9db8]">
                            <span><span className="font-semibold text-[#e8c9ff]">{Number(provider.games_count).toLocaleString()}</span> games</span>
                            <span className="font-mono text-[#806e90]">#{String(index + 1).padStart(2, '0')}</span>
                        </div>
                        <ArrowUpRight size={15} className="absolute right-4 top-4 text-[#806e90] transition group-hover:text-[#d4a9fa]" aria-hidden="true" />
                    </button>
                ))}
            </div>
        </section>
    );
}
