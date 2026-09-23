import React, { useState, useEffect, useRef } from 'react';
import { Link, usePage, router } from '@inertiajs/react';
import { 
    Search, 
    Menu, 
    Plus, 
    Crown, 
    ChevronDown, 
    LogOut, 
    User, 
    Play, 
    Flame, 
    Gamepad2, 
    Radio, 
    Rocket, 
    Fish,
    X,
    Loader2,
    ArrowRight,
    Volume2,
    VolumeX
} from 'lucide-react';
import axios from 'axios';
import { playCoinSound } from '../utils/soundEffects';

const legacyPosters = {
    vs20sweetbonz: '/images/games/sweet-bonanza.avif',
    vs40buffking: '/images/games/buffalo-king.avif',
    vs20sugarush: '/images/games/sugar-rush.avif',
};

function SearchGameItem({ game, onSelect }) {
    const [imageError, setImageError] = useState(false);
    const posterSrc = legacyPosters[game.game_code] || game.banner_url;
    const gameHref = `/game/${game.slug || game.game_code}`;
    const formattedProvider = (game.provider_code || 'PRAGMATIC').replaceAll('_', ' ');

    return (
        <Link
            href={gameHref}
            onClick={onSelect}
            className="flex items-center gap-3.5 p-3 hover:bg-[#201732] transition group"
        >
            {/* Game Thumbnail */}
            <div className="relative w-12 h-12 rounded-xl overflow-hidden bg-[#0c0914] border border-[#332545] flex-shrink-0 flex items-center justify-center">
                {posterSrc && !imageError ? (
                    <img 
                        src={posterSrc} 
                        alt={game.name} 
                        className="w-full h-full object-cover group-hover:scale-110 transition duration-300"
                        onError={() => setImageError(true)}
                        loading="lazy"
                    />
                ) : (
                    <div className="w-full h-full flex items-center justify-center bg-gradient-to-br from-[#241738] to-[#120e1d] text-purple-400">
                        {game.category === 'live' ? <Radio size={18} /> : game.category === 'crash' ? <Rocket size={18} /> : game.category === 'fishing' ? <Fish size={18} /> : <Gamepad2 size={18} />}
                    </div>
                )}
                {game.is_popular && (
                    <span className="absolute top-1 right-1 w-2 h-2 rounded-full bg-amber-400 shadow-[0_0_6px_rgba(251,191,36,0.8)]" />
                )}
            </div>

            {/* Game Info */}
            <div className="flex-1 min-w-0">
                <h4 className="font-heading font-bold text-xs text-white truncate group-hover:text-yellow-300 transition">
                    {game.name.replaceAll('_', ' ')}
                </h4>
                <div className="flex items-center gap-2 mt-1 text-[10px] text-gray-400 font-mono">
                    <span className="text-purple-400 uppercase font-sans font-bold text-[9px] tracking-wide">
                        {formattedProvider}
                    </span>
                    {game.rtp && (
                        <>
                            <span>•</span>
                            <span className="text-emerald-400">{Number(game.rtp).toFixed(1)}% RTP</span>
                        </>
                    )}
                    {game.category && (
                        <>
                            <span>•</span>
                            <span className="uppercase text-[8px] px-1 py-0.2 rounded bg-white/5 text-gray-400">
                                {game.category}
                            </span>
                        </>
                    )}
                </div>
            </div>

            {/* Play Arrow */}
            <div className="w-8 h-8 rounded-xl bg-purple-600/20 group-hover:bg-purple-600 border border-purple-500/30 text-purple-300 group-hover:text-white flex items-center justify-center transition flex-shrink-0 shadow-sm">
                <Play size={14} fill="currentColor" />
            </div>
        </Link>
    );
}

export default function Header({ 
    onToggleSidebar, 
    onOpenAuth, 
    onOpenDeposit, 
    onOpenVip, 
    onOpenCrates,
    onOpenVault,
    onOpenTip,
    searchQuery, 
    setSearchQuery, 
    onSearchSubmit 
}) {
    const { auth } = usePage().props;
    const user = auth?.user;

    const [profileOpen, setProfileOpen] = useState(false);
    const [searchResults, setSearchResults] = useState([]);
    const [isSearching, setIsSearching] = useState(false);
    const [isDropdownOpen, setIsDropdownOpen] = useState(false);
    const [soundOn, setSoundOn] = useState(user?.sound_enabled !== false);

    const toggleSound = () => {
        const nextState = !soundOn;
        setSoundOn(nextState);
        if (nextState) {
            playCoinSound(true);
        }
        axios.post('/api/user/sound-toggle').catch(() => {});
    };

    const searchContainerRef = useRef(null);
    const debounceTimerRef = useRef(null);

    // Handle outside clicks to close dropdown & profile menu
    useEffect(() => {
        const handleClickOutside = (event) => {
            if (searchContainerRef.current && !searchContainerRef.current.contains(event.target)) {
                setIsDropdownOpen(false);
            }
        };

        const handleKeyDown = (event) => {
            if (event.key === 'Escape') {
                setIsDropdownOpen(false);
                setProfileOpen(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, []);

    // Fetch live search results
    useEffect(() => {
        if (debounceTimerRef.current) {
            clearTimeout(debounceTimerRef.current);
        }

        const trimmed = (searchQuery || '').trim();
        if (trimmed.length < 2) {
            setSearchResults([]);
            setIsSearching(false);
            setIsDropdownOpen(false);
            return;
        }

        setIsSearching(true);
        debounceTimerRef.current = setTimeout(async () => {
            try {
                const response = await axios.get('/api/games/search', {
                    params: { q: trimmed }
                });
                setSearchResults(response.data.games || []);
                setIsDropdownOpen(true);
            } catch (error) {
                console.error('Search request failed:', error);
                setSearchResults([]);
            } finally {
                setIsSearching(false);
            }
        }, 180);

        return () => {
            if (debounceTimerRef.current) {
                clearTimeout(debounceTimerRef.current);
            }
        };
    }, [searchQuery]);

    const handleFormSubmit = (e) => {
        e.preventDefault();
        setIsDropdownOpen(false);
        if (onSearchSubmit) {
            onSearchSubmit(searchQuery);
        }
    };

    const handleSelectGame = () => {
        setIsDropdownOpen(false);
    };

    return (
        <header className="nw-header">
            <div className="flex items-center gap-5 min-w-0 flex-1">
                {/* Mobile Menu Button */}
                <button 
                    className="nw-icon-button lg:hidden" 
                    onClick={onToggleSidebar} 
                    aria-label="Open navigation"
                >
                    <Menu size={21} />
                </button>

                {/* Desktop Nav Links */}
                <nav className="nw-header-nav" aria-label="Explore">
                    <Link href="/" className="is-active">Casino</Link>
                    <Link href="/?category=live">Live casino</Link>
                    <button onClick={onOpenVip} className="flex items-center gap-1.5 text-yellow-400 hover:text-yellow-300">
                        <Crown size={14} /> VIP club
                    </button>
                </nav>

                {/* Search Bar with Live Dropdown */}
                <div ref={searchContainerRef} className="relative z-50">
                    <form 
                        className="nw-search relative" 
                        role="search" 
                        onSubmit={handleFormSubmit}
                    >
                        <Search size={17} className="text-gray-400 flex-shrink-0" />
                        
                        <input 
                            aria-label="Search games" 
                            placeholder="Find your next favorite" 
                            value={searchQuery} 
                            onChange={e => setSearchQuery(e.target.value)} 
                            onFocus={() => {
                                if (searchQuery.trim().length >= 2) {
                                    setIsDropdownOpen(true);
                                }
                            }}
                        />

                        {isSearching ? (
                            <Loader2 size={14} className="animate-spin text-purple-400 flex-shrink-0" />
                        ) : searchQuery ? (
                            <button
                                type="button"
                                onClick={() => {
                                    setSearchQuery('');
                                    setSearchResults([]);
                                    setIsDropdownOpen(false);
                                }}
                                className="text-gray-500 hover:text-gray-300 p-0.5"
                                aria-label="Clear search"
                            >
                                <X size={13} />
                            </button>
                        ) : (
                            <kbd>↵</kbd>
                        )}
                    </form>

                    {/* Live Results Dropdown */}
                    {isDropdownOpen && (
                        <div className="absolute top-full left-0 mt-2 w-[340px] sm:w-[420px] max-w-[92vw] bg-[#14101e] border border-[#3e2c50] rounded-2xl shadow-[0_20px_60px_rgba(0,0,0,0.9)] overflow-hidden backdrop-blur-xl z-50 animate-in fade-in slide-in-from-top-2 duration-150">
                            <div className="px-4 py-2.5 bg-[#1a1427] border-b border-[#2e213d] flex items-center justify-between text-[11px]">
                                <span className="text-gray-400 font-medium">
                                    {searchResults.length > 0 ? (
                                        <span>Found <strong className="text-white">{searchResults.length}</strong> matching games</span>
                                    ) : (
                                        <span>Search Results</span>
                                    )}
                                </span>
                                <span className="text-[10px] text-yellow-400/80 font-mono">
                                    Press Enter for all
                                </span>
                            </div>

                            <div className="max-h-[380px] overflow-y-auto no-scrollbar divide-y divide-white/5">
                                {searchResults.length > 0 ? (
                                    searchResults.map((game) => (
                                        <SearchGameItem 
                                            key={game.id} 
                                            game={game} 
                                            onSelect={handleSelectGame} 
                                        />
                                    ))
                                ) : !isSearching ? (
                                    <div className="p-8 text-center space-y-2">
                                        <Gamepad2 size={28} className="text-gray-600 mx-auto" />
                                        <p className="text-xs font-bold text-white">No games found</p>
                                        <p className="text-[11px] text-gray-400">
                                            Try searching with another keyword or provider name
                                        </p>
                                    </div>
                                ) : null}
                            </div>

                            {searchResults.length > 0 && (
                                <button
                                    onClick={handleFormSubmit}
                                    className="w-full px-4 py-2.5 bg-[#171123] hover:bg-[#201830] border-t border-[#2e213d] text-center text-xs font-bold text-yellow-400 flex items-center justify-center gap-1.5 transition"
                                >
                                    <span>View all results in lobby</span>
                                    <ArrowRight size={13} />
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>

            {/* Right Action Icons & Auth Profile */}
            <div className="nw-header-actions flex items-center gap-2.5">
                {/* Audio Sound Toggle */}
                <button
                    onClick={toggleSound}
                    className={`p-2 rounded-xl transition flex items-center justify-center ${
                        soundOn 
                            ? 'bg-purple-900/40 border border-purple-500/40 text-purple-300 shadow-[0_0_10px_rgba(168,85,247,0.3)]' 
                            : 'bg-white/5 hover:bg-white/10 text-gray-500 hover:text-gray-300 border border-white/5'
                    }`}
                    title={soundOn ? 'Sound Effects: ON (Click to Mute)' : 'Sound Effects: MUTED (Click to Unmute)'}
                    aria-label="Toggle Sound Effects"
                >
                    {soundOn ? <Volume2 size={16} className="text-purple-300" /> : <VolumeX size={16} />}
                </button>

                {user ? (
                    <>
                        {/* Cyber Crates Quick Button */}
                        <button 
                            onClick={onOpenCrates}
                            className="px-3 py-2 rounded-xl bg-[#231538] hover:bg-[#2e1d48] border border-purple-500/40 text-purple-300 text-xs font-bold transition flex items-center gap-1.5 relative shadow-sm"
                        >
                            <span>📦 Crates</span>
                            {user.pending_crates_count > 0 && (
                                <span className="w-2 h-2 rounded-full bg-yellow-400 animate-ping" />
                            )}
                        </button>

                        <button className="nw-button nw-button-purple" onClick={onOpenDeposit}>
                            <Plus size={16} /> Get Coins
                        </button>
                        
                        <div className="relative">
                            <button 
                                className="nw-profile" 
                                onClick={() => setProfileOpen(!profileOpen)} 
                                aria-label="Your account" 
                                aria-expanded={profileOpen}
                            >
                                <User size={18} />
                                <span>{user.name}</span>
                                <ChevronDown size={13} />
                            </button>

                            {profileOpen && (
                                <div className="nw-profile-menu">
                                    <div className="px-3 py-2 border-b border-white/10 mb-1">
                                        <span className="block text-xs font-bold text-white truncate">{user.name} {user.surname || ''}</span>
                                        <span className="block text-[10px] text-purple-400 font-mono">{user.user_code || 'NW-PLAYER'}</span>
                                    </div>
                                    <Link 
                                        href="/profile" 
                                        onClick={() => setProfileOpen(false)}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition"
                                    >
                                        <User size={15} className="text-purple-400" />
                                        <span>Player Profile & Stats</span>
                                    </Link>
                                    <button 
                                        onClick={() => {
                                             setProfileOpen(false);
                                             onOpenCrates();
                                        }}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition w-full text-left"
                                    >
                                        <span className="text-sm">📦</span>
                                        <span>Cyber Crates ({user.pending_crates_count || 0})</span>
                                    </button>
                                    <button 
                                        onClick={() => {
                                            setProfileOpen(false);
                                            onOpenVault();
                                        }}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition w-full text-left"
                                    >
                                        <span className="text-sm">🔒</span>
                                        <span>Cyber Vault ({Math.floor(Number(user.vault_balance || 0)).toLocaleString()} Coins)</span>
                                    </button>
                                    <button 
                                        onClick={() => {
                                            setProfileOpen(false);
                                            onOpenTip();
                                        }}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition w-full text-left"
                                    >
                                        <span className="text-sm">💸</span>
                                        <span>Send Tip to Player</span>
                                    </button>
                                    <Link 
                                        href="/profile?tab=syndicate" 
                                        onClick={() => setProfileOpen(false)}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition"
                                    >
                                        <span className="text-sm">👥</span>
                                        <span>Cyber Syndicate (Referrals)</span>
                                    </Link>
                                    <button 
                                        onClick={() => {
                                            setProfileOpen(false);
                                            onOpenVip();
                                        }}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-gray-200 hover:text-white hover:bg-white/5 rounded-xl transition w-full text-left"
                                    >
                                        <Crown size={15} className="text-yellow-400" />
                                        <span>VIP Club (Level {user.vip_level || 1})</span>
                                    </button>
                                    <div className="border-t border-white/5 my-1" />
                                    <button 
                                        onClick={() => router.post('/logout')}
                                        className="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-red-300 hover:text-red-200 hover:bg-red-500/10 rounded-xl transition w-full text-left"
                                    >
                                        <LogOut size={15} />
                                        <span>Log out</span>
                                    </button>
                                </div>
                            )}
                        </div>
                    </>
                ) : (
                    <>
                        <button className="nw-login" onClick={() => onOpenAuth('login')}>
                            Log in
                        </button>
                        <button className="nw-button nw-button-purple" onClick={() => onOpenAuth('register')}>
                            Join now <Plus size={15} />
                        </button>
                    </>
                )}
            </div>
        </header>
    );
}

