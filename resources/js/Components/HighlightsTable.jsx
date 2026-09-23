import React, { useState } from 'react';
import { Trophy, TrendingUp, Radio, Gamepad2 } from 'lucide-react';

export default function HighlightsTable({ highlights = [] }) {
    const [activeTab, setActiveTab] = useState('recent');
    const rows = [...highlights];
    if (activeTab === 'highrollers') rows.sort((a, b) => Number(b.win_amount) - Number(a.win_amount));
    if (activeTab === 'multipliers') rows.sort((a, b) => Number(b.multiplier) - Number(a.multiplier));
    return (
        <section id="highlights-section" className="nw-highlights">
            <div className="nw-section-heading">
                <div className="nw-section-title">
                    <span className="nw-section-icon"><TrendingUp size={21} /></span>
                    <div>
                        <h2>The winning moments</h2>
                        <p>Live community bets and winning multiplier highlights.</p>
                    </div>
                </div>
                <div className="nw-table-tabs">
                    {[
                        ['recent', 'Latest wins', Radio],
                        ['highrollers', 'Big wins', Trophy],
                        ['multipliers', 'Top multipliers', TrendingUp]
                    ].map(([id, label, Icon]) => (
                        <button 
                            key={id} 
                            aria-pressed={id === activeTab} 
                            className={activeTab === id ? 'is-active' : ''} 
                            onClick={() => setActiveTab(id)}
                        >
                            <Icon size={13} />
                            {label}
                        </button>
                    ))}
                </div>
            </div>
            <div className="nw-table-scroll">
                <table className="nw-wins-table">
                    <thead>
                        <tr>
                            <th scope="col">Game</th>
                            <th scope="col">Player</th>
                            <th scope="col">Play amount</th>
                            <th scope="col">Multiplier</th>
                            <th scope="col">Win Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        {rows.slice(0, 6).map((item, index) => (
                            <tr key={item.id || index}>
                                <td>
                                    <span className="nw-table-game">
                                        <Gamepad2 size={16} />
                                        {item.game_name}
                                    </span>
                                </td>
                                <td>
                                    <span className="nw-player-avatar">{item.user_name?.[0] || 'N'}</span>
                                    {item.user_name}
                                </td>
                                <td>
                                    {Math.floor(Number(item.bet_amount)).toLocaleString()} <span className="nw-muted">Coins</span>
                                </td>
                                <td>
                                    <span className={`nw-multiplier ${Number(item.multiplier) >= 100 ? 'is-big' : ''}`}>
                                        {Number(item.multiplier).toFixed(1)}×
                                    </span>
                                </td>
                                <td className="nw-win-amount">
                                    +{Math.floor(Number(item.win_amount)).toLocaleString()} <small>Coins</small>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                {!rows.length && (
                    <div className="nw-empty">
                        <Trophy size={28} />
                        <p>Community highlights will appear here.</p>
                    </div>
                )}
            </div>
        </section>
    );
}

