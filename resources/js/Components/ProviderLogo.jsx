import React from 'react';

/**
 * Authentic vector SVG logos for iGaming providers.
 */
export default function ProviderLogo({ code, name, className = "h-6 w-auto" }) {
    const normalizedCode = (code || '').toUpperCase().trim();

    switch (normalizedCode) {
        case 'PRAGMATIC':
        case 'PRAGMATIC_PLAY':
        case 'PRAGMATIC_SLOT':
            return (
                <svg viewBox="0 0 160 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Pragmatic Play Crown Icon */}
                    <g fill="#F97316">
                        <path d="M12 4L16 11L21 3L26 11L30 4L32 17H10L12 4Z" />
                        <circle cx="12" cy="3" r="1.5" />
                        <circle cx="21" cy="2" r="1.8" />
                        <circle cx="30" cy="3" r="1.5" />
                        <path d="M10 19H32V22C32 23.1 31.1 24 30 24H12C10.9 24 10 23.1 10 22V19Z" opacity="0.9" />
                    </g>
                    {/* Text: PRAGMATIC PLAY */}
                    <text x="38" y="16" fontFamily="'Space Grotesk', 'Montserrat', sans-serif" fontWeight="900" fontSize="11" letterSpacing="0.5" fill="currentColor">
                        PRAGMATIC
                    </text>
                    <text x="38" y="26" fontFamily="'Space Grotesk', 'Montserrat', sans-serif" fontWeight="700" fontSize="8" letterSpacing="3.5" fill="#F97316">
                        PLAY
                    </text>
                </svg>
            );

        case 'PP_LIVE_PRO':
        case 'PRAGMATIC_LIVE':
            return (
                <svg viewBox="0 0 160 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g fill="#EC4899">
                        <path d="M12 4L16 11L21 3L26 11L30 4L32 17H10L12 4Z" />
                        <circle cx="12" cy="3" r="1.5" />
                        <circle cx="21" cy="2" r="1.8" />
                        <circle cx="30" cy="3" r="1.5" />
                        <path d="M10 19H32V22C32 23.1 31.1 24 30 24H12C10.9 24 10 23.1 10 22V19Z" />
                    </g>
                    <text x="38" y="15" fontFamily="'Space Grotesk', 'Montserrat', sans-serif" fontWeight="900" fontSize="10" letterSpacing="0.5" fill="currentColor">
                        PRAGMATIC
                    </text>
                    <g transform="translate(38, 18)">
                        <rect width="32" height="10" rx="3" fill="#EC4899" />
                        <text x="16" y="7.5" textAnchor="middle" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="7" letterSpacing="1" fill="#FFFFFF">
                            LIVE
                        </text>
                    </g>
                </svg>
            );

        case 'PGSOFT':
        case 'PG_SOFT':
            return (
                <svg viewBox="0 0 140 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* PG Hexagon / Pocket Games Symbol */}
                    <g>
                        <path d="M6 10L14 5L22 10V21L14 26L6 21V10Z" fill="none" stroke="#F97316" strokeWidth="2.5" strokeLinejoin="round" />
                        <path d="M14 9V17M14 17L19 14M14 17L9 14" stroke="#F97316" strokeWidth="2" strokeLinecap="round" />
                    </g>
                    <text x="28" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="14" letterSpacing="-0.5" fill="currentColor">
                        PG <tspan fill="#F97316">SOFT</tspan>
                    </text>
                </svg>
            );

        case 'HACKSAW':
        case 'HACKSAW_GAMING':
            return (
                <svg viewBox="0 0 150 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Hacksaw Sawblade Monogram */}
                    <g fill="#EAB308">
                        <circle cx="14" cy="16" r="10" fill="none" stroke="#EAB308" strokeWidth="2" strokeDasharray="3 1.5" />
                        <path d="M10 10V22M18 10V22M10 16H18" stroke="#EAB308" strokeWidth="2.5" strokeLinecap="round" />
                    </g>
                    <text x="30" y="16" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="12" letterSpacing="1" fill="currentColor">
                        HACKSAW
                    </text>
                    <text x="30" y="25" fontFamily="'Space Grotesk', sans-serif" fontWeight="700" fontSize="7" letterSpacing="2.5" fill="#9CA3AF">
                        GAMING
                    </text>
                </svg>
            );

        case 'HABANERO':
            return (
                <svg viewBox="0 0 145 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Habanero Pepper with Flame */}
                    <g transform="translate(6, 4)">
                        <path d="M8 2C8 2 11 0 13 0C13 3 11 5 11 5" stroke="#22C55E" strokeWidth="2" strokeLinecap="round" fill="none" />
                        <path d="M10 5C5 5 2 9 2 14C2 21 8 24 10 24C12 24 18 21 18 14C18 9 15 5 10 5Z" fill="#EF4444" />
                        <path d="M8 9C7 11 7 14 8 16" stroke="#FCA5A5" strokeWidth="1.5" strokeLinecap="round" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="1" fill="currentColor">
                        HABANERO
                    </text>
                </svg>
            );

        case 'SPRIBE':
            return (
                <svg viewBox="0 0 130 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Spribe Turbine / Aviator Wings */}
                    <g transform="translate(6, 7)">
                        <circle cx="9" cy="9" r="8" fill="none" stroke="#F43F5E" strokeWidth="2" />
                        <path d="M9 3V9L14 12" stroke="#F43F5E" strokeWidth="2" strokeLinecap="round" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="14" letterSpacing="2" fill="currentColor">
                        SPRI<tspan fill="#F43F5E">B</tspan>E
                    </text>
                </svg>
            );

        case 'BOOONGO':
        case '3OAKS':
            return (
                <svg viewBox="0 0 145 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Booongo Double Eye Character */}
                    <g fill="#A855F7" transform="translate(6, 8)">
                        <rect width="18" height="15" rx="5" fill="#7C3AED" />
                        <circle cx="6" cy="7.5" r="2.5" fill="#FFFFFF" />
                        <circle cx="12" cy="7.5" r="2.5" fill="#FFFFFF" />
                        <circle cx="7" cy="7.5" r="1.2" fill="#000000" />
                        <circle cx="13" cy="7.5" r="1.2" fill="#000000" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="1.5" fill="currentColor">
                        BOOONGO
                    </text>
                </svg>
            );

        case 'PLAYSON':
            return (
                <svg viewBox="0 0 135 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* Playson Play Diamond */}
                    <g transform="translate(6, 6)">
                        <path d="M4 2L16 10L4 18V2Z" fill="#10B981" />
                        <path d="M12 2L18 10L12 18" stroke="#34D399" strokeWidth="2" fill="none" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="1" fill="currentColor">
                        PLAY<tspan fill="#10B981">SON</tspan>
                    </text>
                </svg>
            );

        case 'CQ9':
        case 'CQ9_GAMING':
            return (
                <svg viewBox="0 0 120 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    {/* CQ9 Shield */}
                    <g transform="translate(6, 5)">
                        <path d="M2 3L10 0L18 3V11C18 16 10 20 10 20C10 20 2 16 2 11V3Z" fill="#3B82F6" opacity="0.9" />
                        <path d="M6 9L9 12L14 7" stroke="#FFFFFF" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="15" letterSpacing="1" fill="currentColor">
                        CQ<tspan fill="#3B82F6">9</tspan>
                    </text>
                </svg>
            );

        case 'EVOPLAY':
            return (
                <svg viewBox="0 0 135 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)">
                        <rect width="18" height="18" rx="4" fill="url(#evoGrad)" />
                        <path d="M7 6L13 10L7 14V6Z" fill="#FFFFFF" />
                        <defs>
                            <linearGradient id="evoGrad" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stopColor="#8B5CF6" />
                                <stop offset="100%" stopColor="#EC4899" />
                            </linearGradient>
                        </defs>
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="1.5" fill="currentColor">
                        EVO<tspan fill="#EC4899">PLAY</tspan>
                    </text>
                </svg>
            );

        case 'NOLIMIT':
        case 'NOLIMIT_CITY':
            return (
                <svg viewBox="0 0 150 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)">
                        <rect width="18" height="18" rx="3" fill="#FACC15" />
                        <path d="M4 4L14 14M4 14L14 4" stroke="#000000" strokeWidth="2.5" strokeLinecap="square" />
                    </g>
                    <text x="30" y="16" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="11" letterSpacing="0.5" fill="currentColor">
                        NOLIMIT
                    </text>
                    <text x="30" y="25" fontFamily="'Space Grotesk', sans-serif" fontWeight="800" fontSize="7" letterSpacing="3" fill="#FACC15">
                        CITY
                    </text>
                </svg>
            );

        case 'EVOLUTION':
        case 'EVOLUTION_GAMING':
            return (
                <svg viewBox="0 0 150 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)" fill="#38BDF8">
                        <path d="M0 0H16V3H3V7H14V10H3V15H16V18H0V0Z" />
                        <circle cx="17" cy="1.5" r="1.5" fill="#38BDF8" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="800" fontSize="13" letterSpacing="1" fill="currentColor">
                        Evolution
                    </text>
                </svg>
            );

        case 'NETENT':
            return (
                <svg viewBox="0 0 135 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)">
                        <circle cx="9" cy="9" r="9" fill="#22C55E" />
                        <path d="M6 13V5L12 13V5" stroke="#FFFFFF" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" fill="none" />
                    </g>
                    <text x="28" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="1" fill="currentColor">
                        NET<tspan fill="#22C55E">ENT</tspan>
                    </text>
                </svg>
            );

        case 'DREAMTECH':
        case 'DREAM_TECH':
            return (
                <svg viewBox="0 0 145 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)" fill="#6366F1">
                        <polygon points="9,0 12,6 18,9 12,12 9,18 6,12 0,9 6,6" />
                    </g>
                    <text x="28" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="12" letterSpacing="0.5" fill="currentColor">
                        DREAM<tspan fill="#818CF8">TECH</tspan>
                    </text>
                </svg>
            );

        case 'FASTSPIN':
        case 'FAST_SPIN':
            return (
                <svg viewBox="0 0 140 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)" fill="#EAB308">
                        <path d="M10 0L3 11H9L7 18L15 8H9L11 0H10Z" />
                    </g>
                    <text x="26" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="13" letterSpacing="0.5" fill="currentColor">
                        FAST<tspan fill="#EAB308">SPIN</tspan>
                    </text>
                </svg>
            );

        case 'TOPTREND':
        case 'TOPTREND_GAMING':
        case 'TTG':
            return (
                <svg viewBox="0 0 145 32" fill="currentColor" className={className} xmlns="http://www.w3.org/2000/svg">
                    <g transform="translate(6, 6)">
                        <path d="M2 14L8 4L14 14H2Z" fill="#F43F5E" />
                        <path d="M8 14L14 4L20 14H8Z" fill="#FB7185" opacity="0.8" />
                    </g>
                    <text x="30" y="21" fontFamily="'Space Grotesk', sans-serif" fontWeight="900" fontSize="12" letterSpacing="0.5" fill="currentColor">
                        TOPTREND
                    </text>
                </svg>
            );

        default:
            return (
                <div className="flex items-center gap-1.5 font-heading font-black text-sm text-gray-300 tracking-wide uppercase">
                    <span className="w-2 h-2 rounded-full bg-purple-500 shadow-[0_0_6px_rgba(168,85,247,0.8)]" />
                    <span>{name || code}</span>
                </div>
            );
    }
}
