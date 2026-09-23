import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Play, Flame, Heart } from 'lucide-react';

const getFallbackBanner = (game) => {
    const code = game.game_code || game.slug;
    const provider = (game.provider_code || '').toUpperCase();
    if (provider.includes('PGSOFT')) {
        return `https://assets.bd34fgabh.com/img/pgsoft/${code}.jpg`;
    }
    if (provider.includes('HACKSAW')) {
        return `https://www-live.hacksawgaming.com/casino_thumbnails/${code}.jpg`;
    }
    if (provider.includes('SPRIBE')) {
        return `https://spribe.co/assets/games/${code}/thumbnail.png`;
    }
    return `https://assets.bd34fgabh.com/apps/game-assets/${code}/${code}_800x600_NB.avif`;
};

export default function GameCard({ game }) {
    const { auth } = usePage().props;
    const [favorite, setFavorite] = useState(() => { try { return JSON.parse(localStorage.getItem('neonwin-favorites') || '[]').includes(game.id); } catch { return false; } });
    const [imageSrc, setImageSrc] = useState(() => game.banner_url || getFallbackBanner(game));

    const toggleFavorite = () => {
        const nextState = !favorite;
        setFavorite(nextState);
        try {
            const ids = JSON.parse(localStorage.getItem('neonwin-favorites') || '[]');
            localStorage.setItem('neonwin-favorites', JSON.stringify(!nextState ? ids.filter(id => id !== game.id) : [...new Set([...ids, game.id])]));
        } catch {}
        // Sync with backend if available
        import('axios').then(({ default: axios }) => {
            axios.post('/api/games/favorite', { game_code: game.game_code || game.slug }).catch(() => {});
        });
    };

    const handlePlayClick = (e) => {
        if (!auth?.user) {
            e.preventDefault();
            window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { mode: 'register' } }));
        }
    };

    const handleImageError = () => {
        const fallback = getFallbackBanner(game);
        if (imageSrc !== fallback) {
            setImageSrc(fallback);
        }
    };

    const href = `/game/${game.slug || game.game_code}`;
    return <article className="nw-game-card">
        <div className="nw-game-poster">
            <img 
                src={imageSrc} 
                onError={handleImageError} 
                alt={game.name} 
                loading="lazy" 
                className="w-full h-full object-cover"
            />
            <div className="nw-game-badges">{game.category === 'live' ? <span className="nw-badge-live"><i /> LIVE</span> : game.is_popular ? <span className="nw-badge-hot"><Flame size={10} fill="currentColor" /> HOT</span> : null}</div>
            <button className={`nw-favorite ${favorite ? 'is-active' : ''}`} aria-label={`${favorite ? 'Remove' : 'Add'} ${game.name} ${favorite ? 'from' : 'to'} favorites`} aria-pressed={favorite} onClick={toggleFavorite}><Heart size={14} fill={favorite ? 'currentColor' : 'none'} /></button>
            <div className="nw-game-overlay">
                <Link href={href} onClick={handlePlayClick} className="nw-game-play" aria-label={`Play ${game.name}`}>
                    <Play size={23} fill="currentColor" />
                </Link>
                {auth?.user ? (
                    <Link href={href} className="nw-game-demo">Play now</Link>
                ) : (
                    <button type="button" onClick={handlePlayClick} className="nw-game-demo">Sign in to play</button>
                )}
            </div>
        </div>
        <div className="nw-game-info">
            <h3><Link href={href} onClick={handlePlayClick}>{game.name.replaceAll('_', ' ')}</Link></h3>
            <div><span>{game.provider_code.replaceAll('_', ' ')}</span>{game.rtp && <span className="nw-game-rtp">{Number(game.rtp).toFixed(1)}% RTP</span>}</div>
        </div>
    </article>;
}
