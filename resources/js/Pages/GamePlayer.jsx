import React, { useState, useEffect, useRef } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { 
    ArrowLeft, 
    Maximize, 
    Minimize, 
    RefreshCw, 
    Coins, 
    Sparkles, 
    ShieldCheck, 
    Zap,
    Flame,
    Heart,
    Share2,
    Info,
    ChevronRight,
    Gamepad2
} from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';
import GameCard from '../Components/GameCard';

export default function GamePlayer({ game, launchUrl, isMock = false, relatedGames = [] }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [isLoading, setIsLoading] = useState(true);
    const [isFullscreen, setIsFullscreen] = useState(false);
    const [currentBalance, setCurrentBalance] = useState(user?.game_balance || 0);
    const [favorite, setFavorite] = useState(() => {
        try {
            return JSON.parse(localStorage.getItem('neonwin-favorites') || '[]').includes(game.id);
        } catch {
            return false;
        }
    });

    const iframeRef = useRef(null);
    const playerContainerRef = useRef(null);

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsLoading(false);
        }, 1200);

        // Listen for real-time postMessage events from the game iframe
        const handleMessage = (event) => {
            if (event.data && event.data.type === 'NEONWIN_BALANCE_UPDATE') {
                setCurrentBalance(event.data.balance);
            }
        };

        const handleFullscreenChange = () => {
            setIsFullscreen(Boolean(document.fullscreenElement));
        };

        window.addEventListener('message', handleMessage);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('webkitfullscreenchange', handleFullscreenChange);

        return () => {
            clearTimeout(timer);
            window.removeEventListener('message', handleMessage);
            document.removeEventListener('fullscreenchange', handleFullscreenChange);
            document.removeEventListener('webkitfullscreenchange', handleFullscreenChange);
        };
    }, []);

    const toggleFavorite = () => {
        setFavorite(!favorite);
        try {
            const ids = JSON.parse(localStorage.getItem('neonwin-favorites') || '[]');
            localStorage.setItem(
                'neonwin-favorites',
                JSON.stringify(favorite ? ids.filter(id => id !== game.id) : [...new Set([...ids, game.id])])
            );
        } catch {
            // local storage disabled
        }
    };

    const toggleFullscreen = () => {
        if (!document.fullscreenElement) {
            if (playerContainerRef.current?.requestFullscreen) {
                playerContainerRef.current.requestFullscreen().catch(err => {
                    console.error('Fullscreen request failed:', err);
                });
            } else if (playerContainerRef.current?.webkitRequestFullscreen) {
                playerContainerRef.current.webkitRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            }
        }
    };

    const handleRefresh = () => {
        setIsLoading(true);
        if (iframeRef.current) {
            iframeRef.current.src = iframeRef.current.src;
        }
        setTimeout(() => setIsLoading(false), 1000);
    };

    const formattedProvider = (game.provider_code || 'PRAGMATIC').replaceAll('_', ' ');
    const formattedCategory = (game.category || 'slots').toUpperCase();

    return (
        <MainLayout>
            {({ openDeposit }) => (
                <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-6 py-2">
                    <Head title={`${game.name} - Play on Neonwin`} />

                    {/* Top Breadcrumb & Quick Controls */}
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        
                        {/* Breadcrumbs & Title */}
                        <div className="flex items-center gap-2 text-xs text-gray-400">
                            <Link 
                                href="/" 
                                className="hover:text-purple-300 transition flex items-center gap-1 font-medium"
                            >
                                <ArrowLeft className="w-3.5 h-3.5" />
                                <span>Lobby</span>
                            </Link>
                            <ChevronRight className="w-3.5 h-3.5 text-gray-600" />
                            <Link 
                                href={`/?category=${game.category || 'slots'}`}
                                className="hover:text-purple-300 transition uppercase tracking-wider font-semibold text-[11px]"
                            >
                                {formattedCategory}
                            </Link>
                            <ChevronRight className="w-3.5 h-3.5 text-gray-600" />
                            <span className="text-white font-bold truncate max-w-[200px] sm:max-w-none">
                                {game.name}
                            </span>
                        </div>

                        {/* Top Action Toolbar */}
                        <div className="flex items-center gap-2.5">
                            {/* Live Balance */}
                            <div className="px-3.5 py-1.5 rounded-xl bg-[#171926] border border-[#2b2a3d] flex items-center gap-2 shadow-inner">
                                <Coins className="w-3.5 h-3.5 text-yellow-400 animate-spin-slow" />
                                <span className="text-xs font-mono font-bold text-white tracking-tight">
                                    {Math.floor(Number(currentBalance)).toLocaleString()} <span className="text-[10px] text-yellow-400">Coins</span>
                                </span>
                            </div>

                            {/* Favorite Button */}
                            <button
                                onClick={toggleFavorite}
                                className={`p-2 rounded-xl border transition flex items-center justify-center ${
                                    favorite 
                                        ? 'bg-rose-500/20 border-rose-500/40 text-rose-400' 
                                        : 'bg-[#171926] border-[#2b2a3d] text-gray-400 hover:text-white hover:border-purple-500/40'
                                }`}
                                title={favorite ? 'Remove from favorites' : 'Add to favorites'}
                                aria-label="Favorite"
                            >
                                <Heart className="w-4 h-4" fill={favorite ? 'currentColor' : 'none'} />
                            </button>

                            {/* Reload Button */}
                            <button
                                onClick={handleRefresh}
                                className="p-2 rounded-xl bg-[#171926] border border-[#2b2a3d] text-gray-400 hover:text-white hover:border-purple-500/40 transition"
                                title="Reload game"
                                aria-label="Reload game"
                            >
                                <RefreshCw className={`w-4 h-4 ${isLoading ? 'animate-spin text-purple-400' : ''}`} />
                            </button>

                            {/* Fullscreen Button */}
                            <button
                                onClick={toggleFullscreen}
                                className="px-3 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 text-white text-xs font-bold transition flex items-center gap-1.5 shadow-[0_0_20px_rgba(147,51,234,0.3)]"
                                title="Toggle Fullscreen"
                            >
                                {isFullscreen ? (
                                    <>
                                        <Minimize className="w-4 h-4" />
                                        <span className="hidden sm:inline">Exit Fullscreen</span>
                                    </>
                                ) : (
                                    <>
                                        <Maximize className="w-4 h-4" />
                                        <span className="hidden sm:inline">Fullscreen</span>
                                    </>
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Game Stage Container */}
                    <div 
                        ref={playerContainerRef} 
                        className={`w-full bg-[#080a10] relative rounded-2xl overflow-hidden border border-[#2c2844] shadow-[0_12px_45px_rgba(0,0,0,0.85)] flex flex-col items-center justify-center transition-all duration-300 ${
                            isFullscreen 
                                ? 'fixed inset-0 z-50 rounded-none border-0 h-screen max-h-none' 
                                : 'h-[520px] sm:h-[620px] lg:h-[72vh] max-h-[820px] min-h-[440px]'
                        }`}
                    >
                        {/* Fullscreen Floating Exit Button */}
                        {isFullscreen && (
                            <button
                                onClick={toggleFullscreen}
                                className="absolute top-4 right-4 z-50 px-3 py-1.5 rounded-xl bg-black/70 hover:bg-black/90 backdrop-blur-md border border-white/20 text-white text-xs font-bold flex items-center gap-2 transition"
                            >
                                <Minimize className="w-4 h-4" />
                                <span>Exit Fullscreen (ESC)</span>
                            </button>
                        )}

                        {/* Cyber Preloader Animation */}
                        {isLoading && (
                            <div className="absolute inset-0 z-40 bg-[#0c0e17] flex flex-col items-center justify-center space-y-5">
                                {/* Glowing Neon Logo Mark */}
                                <div className="relative flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-br from-purple-600 via-fuchsia-600 to-indigo-700 shadow-[0_0_40px_rgba(168,85,247,0.7)] animate-pulse">
                                    <svg className="w-10 h-10 text-white" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M12 2L14.5 9.5L22 12L14.5 14.5L12 22L9.5 14.5L2 12L9.5 9.5L12 2Z"/>
                                    </svg>
                                </div>

                                <div className="text-center space-y-1.5">
                                    <div className="font-heading font-black text-xl text-white tracking-wider uppercase">
                                        NEON<span className="text-purple-400">WIN</span>
                                    </div>
                                    <div className="text-xs text-gray-400 font-mono">
                                        Launching <span className="text-purple-300 font-bold">{game.name}</span>
                                    </div>
                                </div>

                                {/* Loading Progress Bar */}
                                <div className="w-44 h-1.5 bg-[#1a1d2e] rounded-full overflow-hidden">
                                    <div className="h-full bg-gradient-to-r from-purple-500 via-pink-500 to-cyan-400 animate-shimmer w-full" />
                                </div>

                                <div className="text-[10px] text-gray-500 flex items-center gap-1.5 pt-1">
                                    <ShieldCheck className="w-3.5 h-3.5 text-emerald-400" />
                                    <span>Certified RNG • Provably Fair Gameplay</span>
                                </div>
                            </div>
                        )}

                        {/* Game Iframe */}
                        <iframe
                            ref={iframeRef}
                            src={launchUrl}
                            title={game.name}
                            className="w-full h-full border-0"
                            allow="autoplay; fullscreen; encrypted-media; screen-wake-lock; clipboard-write; clipboard-read"
                            onLoad={() => setIsLoading(false)}
                        />
                    </div>

                    {/* Bottom Info & Stats Bar */}
                    <div className="p-4 sm:p-5 rounded-2xl bg-[#121420] border border-[#232435] flex flex-wrap items-center justify-between gap-4">
                        <div className="flex items-center gap-4">
                            <div className="w-12 h-12 rounded-xl bg-gradient-to-tr from-purple-600/30 to-indigo-600/30 border border-purple-500/30 flex items-center justify-center text-xl shadow-inner">
                                🎰
                            </div>
                            <div>
                                <h1 className="font-heading font-black text-base sm:text-lg text-white leading-tight">
                                    {game.name}
                                </h1>
                                <div className="flex items-center gap-2.5 mt-1 text-xs text-gray-400">
                                    <span className="font-bold text-purple-400 uppercase tracking-wide">
                                        {formattedProvider}
                                    </span>
                                    <span>•</span>
                                    <span>RTP {Number(game.rtp || 96.50).toFixed(2)}%</span>
                                    <span>•</span>
                                    <span className="text-emerald-400 font-semibold flex items-center gap-1">
                                        <ShieldCheck className="w-3.5 h-3.5" /> Fair Play
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Action buttons */}
                        <div className="flex items-center gap-3">
                            <button 
                                onClick={openDeposit}
                                className="px-4 py-2 rounded-xl bg-gradient-to-r from-amber-500 to-yellow-400 hover:from-amber-400 hover:to-yellow-300 text-black font-extrabold text-xs tracking-wide transition shadow-[0_0_15px_rgba(245,158,11,0.3)] flex items-center gap-1.5"
                            >
                                <Zap className="w-3.5 h-3.5 fill-black" />
                                <span>GET SC COINS</span>
                            </button>
                        </div>
                    </div>

                    {/* Related Games from Provider / Category */}
                    {relatedGames.length > 0 && (
                        <div className="mt-6 flex flex-col gap-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="font-heading font-bold text-base sm:text-lg text-white">
                                        More Games by {formattedProvider}
                                    </h2>
                                    <p className="text-xs text-gray-400">
                                        Players who enjoyed {game.name} also played these top hits
                                    </p>
                                </div>
                                <Link 
                                    href={`/?provider=${game.provider_code}`}
                                    className="text-xs font-bold text-purple-400 hover:text-purple-300 flex items-center gap-1 transition"
                                >
                                    View all <ChevronRight className="w-3.5 h-3.5" />
                                </Link>
                            </div>

                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3.5">
                                {relatedGames.map(relGame => (
                                    <GameCard key={relGame.id} game={relGame} />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </MainLayout>
    );
}
