import React, { useState, useEffect } from 'react';
import { Trophy, ArrowUpRight } from 'lucide-react';

export default function TournamentsBanner({ tournament, onExploreGames }) {
    const [now, setNow] = useState(Date.now());
    const deadline = tournament?.ends_at ? new Date(tournament.ends_at).getTime() : NaN;
    const remaining = Number.isFinite(deadline) ? Math.max(0, deadline - now) : 0;
    const active = remaining > 0;
    useEffect(() => {
        if (!Number.isFinite(deadline)) return;
        setNow(Date.now());
        const timer = setInterval(() => setNow(Date.now()), 1000);
        return () => clearInterval(timer);
    }, [deadline]);
    const values = [Math.floor(remaining / 86400000), Math.floor(remaining / 3600000) % 24, Math.floor(remaining / 60000) % 60, Math.floor(remaining / 1000) % 60];
    return <section id="tournaments-section" className="nw-tournament">
        <div className="nw-tournament-emblem"><Trophy size={43} strokeWidth={1.4} /></div>
        <div className="nw-tournament-copy"><span className="nw-eyebrow">{active ? 'THE RACE IS ON' : 'NEONWIN TOURNAMENTS'}</span><h2>{tournament?.name || tournament?.title || 'Big moments start here.'}</h2><p>{tournament?.prize_pool ? <><strong>{Math.floor(Number(tournament.prize_pool)).toLocaleString('en-US')} Coins</strong> prize pool</> : 'Explore the games. Find your next favorite.'}</p></div>
        <div className="nw-countdown-wrap"><span className="nw-eyebrow">{active ? 'TIME LEFT TO PLAY' : tournament ? 'THIS TOURNAMENT HAS ENDED' : 'NEXT TOURNAMENT COMING SOON'}</span>{active && <div className="nw-countdown" aria-label="Tournament countdown">{['Days', 'Hours', 'Mins', 'Secs'].map((label, index) => <div key={label}><strong>{String(values[index]).padStart(2, '0')}</strong><small>{label}</small></div>)}</div>}</div>
        <button className="nw-button nw-button-purple" onClick={onExploreGames}>Explore games <ArrowUpRight size={15} /></button>
    </section>;
}
