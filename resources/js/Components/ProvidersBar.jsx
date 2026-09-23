import React from 'react';
import { ArrowUpRight } from 'lucide-react';

export default function ProvidersBar({ providers = [], onSelectProvider }) {
    return <section className="nw-providers" aria-label="Game providers"><div className="nw-section-heading"><span className="nw-eyebrow">GREAT GAMES. GREAT CREATORS.</span><span className="nw-provider-count">{providers.length} providers to explore</span></div><div className="nw-provider-brands">{providers.slice(0, 8).map(provider => <button key={provider.code} onClick={() => onSelectProvider(provider.code)}><span>{provider.name.replace('PRAGMATIC PLAY LIVE - PRO', 'Pragmatic Live')}</span><ArrowUpRight size={13} /></button>)}</div></section>;
}
