import React, { useState } from 'react';
import { Link } from '@inertiajs/react';
import { Play, Flame, Heart, Gamepad2 } from 'lucide-react';

const legacyPosters = {
    vs20sweetbonz: '/images/games/sweet-bonanza.avif',
    vs40buffking: '/images/games/buffalo-king.avif',
    vs20sugarush: '/images/games/sugar-rush.avif',
};

export default function GameCard({ game }) {
    const [favorite, setFavorite] = useState(() => { try { return JSON.parse(localStorage.getItem('neonwin-favorites') || '[]').includes(game.id); } catch { return false; } });
    const [imageError, setImageError] = useState(false);
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
    const href = `/game/${game.slug || game.game_code}`;
    return <article className="nw-game-card">
        <div className="nw-game-poster">
            {game.banner_url && !imageError ? <img src={legacyPosters[game.game_code] || game.banner_url} onError={() => setImageError(true)} alt={game.name} loading="lazy" /> : <div className={`nw-game-fallback nw-fallback-${game.category || 'slots'}`}><Gamepad2 size={42} /><strong>{game.name}</strong><span>{game.provider_code}</span></div>}
            <div className="nw-game-badges">{game.category === 'live' ? <span className="nw-badge-live"><i /> LIVE</span> : game.is_popular ? <span className="nw-badge-hot"><Flame size={10} fill="currentColor" /> HOT</span> : null}</div>
            <button className={`nw-favorite ${favorite ? 'is-active' : ''}`} aria-label={`${favorite ? 'Remove' : 'Add'} ${game.name} ${favorite ? 'from' : 'to'} favorites`} aria-pressed={favorite} onClick={toggleFavorite}><Heart size={14} fill={favorite ? 'currentColor' : 'none'} /></button>
            <div className="nw-game-overlay"><Link href={href} className="nw-game-play" aria-label={`Play ${game.name}`}><Play size={23} fill="currentColor" /></Link><Link href={`${href}?demo=1`} className="nw-game-demo">Try demo</Link></div>
        </div>
        <div className="nw-game-info"><h3><Link href={href}>{game.name.replaceAll('_', ' ')}</Link></h3><div><span>{game.provider_code.replaceAll('_', ' ')}</span>{game.rtp && <span className="nw-game-rtp">{Number(game.rtp).toFixed(1)}% RTP</span>}</div></div>
    </article>;
}
