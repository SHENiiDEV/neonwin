import React from 'react';
import { Link } from '@inertiajs/react';
import { ArrowUpRight, Trophy } from 'lucide-react';

export default function FeatureBanners() {
    return <section className="nw-feature-grid" aria-label="More ways to play">
        <a href="#tournaments-section" className="nw-feature nw-feature-tournament"><img src="/images/landing/neon-trophy.png" alt="" loading="lazy" /><div className="nw-feature-content"><span className="nw-eyebrow"><Trophy size={12} /> THE COMPETITIVE SIDE OF PLAY</span><h2>A little friendly<br /><em>competition.</em></h2><p>Discover what’s happening in tournaments.</p><span className="nw-feature-button">Explore tournaments <ArrowUpRight size={15} /></span></div></a>
        <Link href="/?category=live" className="nw-feature nw-feature-live"><div className="nw-card-art" aria-hidden="true"><div className="nw-playing-card">A<small>♠</small><b>♠</b></div><div className="nw-playing-card">K<small>♦</small><b>♦</b></div><div className="nw-poker-chip">N</div></div><div className="nw-feature-content"><span className="nw-eyebrow"><i className="nw-live-dot" /> THE LIVE CASINO EXPERIENCE</span><h2>Your table.<br /><em>Your moment.</em></h2><p>Real dealers. Classic games. All the atmosphere.</p><span className="nw-feature-button">Take a seat <ArrowUpRight size={15} /></span></div></Link>
    </section>;
}
