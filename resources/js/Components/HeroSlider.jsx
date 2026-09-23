import React, { useEffect, useState } from 'react';
import { ArrowUpRight, ArrowRight, Gift, Crown, ChevronLeft, ChevronRight, Pause, Play, Sparkles } from 'lucide-react';

const slides = [
    { eyebrow: 'YOUR NEXT FAVORITE PLACE TO PLAY', title: 'Good times.', accent: 'Golden energy.', text: 'Big favorites. Fresh discoveries. A little neon magic.\nFind your next great game at Neonwin.', button: 'Let’s play', image: '/images/landing/neon-lucky-cat.png', action: 'games' },
    { eyebrow: 'MAKE EVERY VISIT A LITTLE BRIGHTER', title: 'Your daily', accent: 'dose of play.', text: 'Drop in, explore your rewards and discover\nsomething new in the casino lobby.', button: 'Explore rewards', image: '/images/landing/neon-lucky-cat.png', action: 'rewards' },
    { eyebrow: 'WELCOME TO THE INNER CIRCLE', title: 'More play.', accent: 'More privileges.', text: 'Explore the Neonwin VIP club. Five levels,\nnew milestones and rewards along the way.', button: 'Explore VIP club', image: '/images/landing/neon-trophy.png', action: 'vip' },
];

export default function HeroSlider({ onOpenDeposit, onOpenVip }) {
    const [current, setCurrent] = useState(0);
    const [paused, setPaused] = useState(false);
    const [hovered, setHovered] = useState(false);
    const [reducedMotion, setReducedMotion] = useState(() => typeof window !== 'undefined' && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
    useEffect(() => {
        const media = window.matchMedia('(prefers-reduced-motion: reduce)');
        const update = () => setReducedMotion(media.matches);
        media.addEventListener('change', update);
        return () => media.removeEventListener('change', update);
    }, []);
    useEffect(() => {
        if (paused || hovered || reducedMotion) return;
        const timer = setInterval(() => setCurrent(value => (value + 1) % slides.length), 8000);
        return () => clearInterval(timer);
    }, [paused, hovered, reducedMotion]);
    const slide = slides[current];
    return (
        <section className="nw-hero-grid" aria-label="Neonwin promotions">
            <div className="nw-hero" role="region" aria-roledescription="carousel" aria-label="Featured promotions" onMouseEnter={() => setHovered(true)} onMouseLeave={() => setHovered(false)} onFocusCapture={() => setPaused(true)}>
                <img className="nw-hero-art" src={slide.image} alt="" fetchPriority="high" />
                <div className="nw-hero-shade" />
                <div className="nw-hero-content" key={current}>
                    <span className="nw-eyebrow"><span className="nw-little-star">✦</span> {slide.eyebrow}</span>
                    <h1>{slide.title}<br /><em>{slide.accent}</em></h1>
                    <p>{slide.text}</p>
                    {slide.action === 'games' ? <a href="#popular-games" className="nw-button nw-button-gold">{slide.button}<ArrowRight size={17} /></a> : <button className="nw-button nw-button-gold" onClick={slide.action === 'vip' ? onOpenVip : onOpenDeposit}>{slide.button}<ArrowRight size={17} /></button>}
                    <span className="nw-hero-note">18+ · Social casino · Play responsibly</span>
                </div>
                <div className="nw-hero-controls"><div className="nw-hero-dots">{slides.map((item, index) => <button key={item.title} onClick={() => setCurrent(index)} aria-label={`Show promotion ${index + 1}`} aria-current={current === index ? 'true' : undefined} className={current === index ? 'is-active' : ''} />)}</div><div className="flex gap-1"><button onClick={() => setPaused(!paused)} aria-label={paused ? 'Play slideshow' : 'Pause slideshow'}>{paused ? <Play size={12} /> : <Pause size={12} />}</button><button onClick={() => setCurrent((current + slides.length - 1) % slides.length)} aria-label="Previous promotion"><ChevronLeft size={16} /></button><button onClick={() => setCurrent((current + 1) % slides.length)} aria-label="Next promotion"><ChevronRight size={16} /></button></div></div>
            </div>
            <div className="nw-hero-side">
                <button className="nw-mini-promo nw-mini-gift" onClick={onOpenDeposit}><span className="nw-eyebrow"><Sparkles size={12} /> A LITTLE EXTRA, EVERY DAY</span><strong>Your daily<br /><span>happy drop.</span></strong><span className="nw-promo-link">Explore rewards <ArrowUpRight size={15} /></span><div className="nw-gift-art" aria-hidden="true"><Gift size={95} strokeWidth={1.15} /></div></button>
                <button className="nw-mini-promo nw-mini-vip" onClick={onOpenVip}><span className="nw-eyebrow">YOU’RE ON THE GUEST LIST</span><strong>Play your way.<br /><span>Go VIP.</span></strong><span className="nw-promo-link">Discover the club <ArrowUpRight size={15} /></span><div className="nw-crown-art" aria-hidden="true"><Crown size={98} strokeWidth={1.2} fill="currentColor" /></div></button>
            </div>
        </section>
    );
}
