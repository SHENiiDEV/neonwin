import React from 'react';
import GameCard from './GameCard';
import { ArrowUpRight, Flame, Gamepad2 } from 'lucide-react';

export default function GameGrid({ id, title = 'Popular right now', subtitle, games = [], providers = [], selectedProvider = 'all', onSelectProvider, onViewAll, icon: Icon = Flame }) {
    return <section id={id} className="nw-game-section">
        <div className="nw-section-heading"><div className="nw-section-title"><span className="nw-section-icon"><Icon size={21} /></span><div><h2>{title}</h2>{subtitle && <p>{subtitle}</p>}</div></div>{onViewAll && <button className="nw-view-all" onClick={onViewAll}>View all <ArrowUpRight size={15} /></button>}</div>
        {providers.length > 0 && <div className="nw-provider-tabs" aria-label="Filter by provider"><button className={selectedProvider === 'all' ? 'is-active' : ''} aria-pressed={selectedProvider === 'all'} onClick={() => onSelectProvider('all')}>All providers</button>{providers.map(provider => <button key={provider.code} aria-pressed={selectedProvider.toUpperCase() === provider.code.toUpperCase()} className={selectedProvider.toUpperCase() === provider.code.toUpperCase() ? 'is-active' : ''} onClick={() => onSelectProvider(provider.code)}>{provider.name.replace('PRAGMATIC PLAY LIVE - PRO', 'Pragmatic Live')}<span>{provider.games_count}</span></button>)}</div>}
        {games.length ? <div className="nw-game-grid">{games.map(game => <GameCard key={game.id} game={game} />)}</div> : <div className="nw-empty"><Gamepad2 size={30} /><h3>No games found</h3><p>Try another category, provider or search.</p>{onSelectProvider && <button className="nw-button nw-button-purple" onClick={() => onSelectProvider('all')}>Reset provider</button>}</div>}
    </section>;
}
