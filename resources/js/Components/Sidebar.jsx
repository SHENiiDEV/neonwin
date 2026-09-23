import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Cherry, Radio, Rocket, Fish, Flame, Gift, Trophy, Crown, Headphones, ShieldCheck, X, ChevronRight, Zap } from 'lucide-react';

export function Brand() {
    return <span className="nw-brand"><span className="nw-brand-mark"><Zap size={24} fill="currentColor" /></span><span>neon<span>win</span><small>PLAY IN YOUR ELEMENT</small></span></span>;
}

function WalletBalanceDisplay({ balance = 0 }) {
    const num = Number(balance || 0);
    const formatted = num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    
    // Choose dynamic text size based on character length so it never overflows
    let sizeClass = 'text-[22px]';
    if (formatted.length > 15) {
        sizeClass = 'text-[13px]';
    } else if (formatted.length > 12) {
        sizeClass = 'text-[15px]';
    } else if (formatted.length > 9) {
        sizeClass = 'text-[18px]';
    }

    return (
        <div className="flex items-baseline justify-between gap-1.5 my-2 min-w-0 w-full overflow-hidden">
            <span 
                className={`font-heading font-bold text-white tracking-tight tabular-nums truncate ${sizeClass}`}
                title={`${formatted} SC`}
            >
                {formatted}
            </span>
            <span className="text-xs font-bold text-yellow-400 shrink-0 font-sans tracking-wide">
                SC
            </span>
        </div>
    );
}

export default function Sidebar({ isOpen, setIsOpen, onOpenDeposit, onOpenVip, onOpenCrates, onOpenVault, onOpenTip }) {
    const { auth, filters = {} } = usePage().props;
    const category = filters.category || 'all';
    const links = [
        ['all', 'Casino lobby', LayoutGrid], ['slots', 'Slots', Cherry], ['live', 'Live casino', Radio],
        ['crash', 'Crash & fast games', Rocket], ['fishing', 'Fish hunter', Fish], ['popular', 'Popular games', Flame],
    ];
    return (
        <aside className={`nw-sidebar ${isOpen ? 'is-open' : ''}`} aria-label="Main navigation">
            <div className="nw-sidebar-brand"><Link href="/" aria-label="Neonwin home"><Brand /></Link><button className="nw-icon-button lg:hidden" aria-label="Close navigation" onClick={() => setIsOpen(false)}><X size={18} /></button></div>
            <div className="nw-wallet">
                <span className="nw-eyebrow">YOUR PLAY WALLET <span className="nw-currency">SC</span></span>
                <Link href={auth?.user ? "/profile" : "#"} onClick={() => setIsOpen(false)} className="block hover:opacity-85 transition min-w-0 w-full">
                    <WalletBalanceDisplay balance={auth?.user?.game_balance} />
                </Link>
                <button onClick={onOpenDeposit} className="nw-button nw-button-purple w-full"><Gift size={15} /> Get Sweeps Coins</button>
            </div>
            <nav className="nw-navigation">
                <span className="nw-nav-label">LET’S PLAY</span>
                {links.map(([id, label, Icon]) => <Link key={id} href={`/?category=${id}`} onClick={() => setIsOpen(false)} className={`nw-nav-link ${category === id ? 'is-active' : ''}`}><Icon size={18} /><span>{label}</span>{id === 'live' && <i className="nw-live-dot" />}{id === 'slots' && <span className="nw-nav-tag">HOT</span>}</Link>)}
                <span className="nw-nav-label nw-nav-divider">MORE TO DISCOVER</span>
                {auth?.user && (
                    <>
                        <Link className="nw-nav-link" href="/profile" onClick={() => setIsOpen(false)}>
                            <Zap size={18} className="text-purple-400" /><span>Player profile & stats</span>
                        </Link>
                        <button className="nw-nav-link" onClick={() => { setIsOpen(false); onOpenCrates(); }}>
                            <Gift size={18} className="text-yellow-400" /><span>Cyber Crates</span>{auth.user.pending_crates_count > 0 && <span className="nw-nav-tag">NEW</span>}
                        </button>
                        <button className="nw-nav-link" onClick={() => { setIsOpen(false); onOpenVault(); }}>
                            <ShieldCheck size={18} className="text-cyan-400" /><span>Piggy Bank Vault</span>
                        </button>
                    </>
                )}
                <button className="nw-nav-link" onClick={onOpenDeposit}><Gift size={18} /><span>Bonuses & rewards</span></button>
                <Link className="nw-nav-link" href="/#tournaments-section" onClick={() => setIsOpen(false)}><Trophy size={18} /><span>Tournaments</span></Link>
                <button className="nw-nav-link" onClick={onOpenVip}><Crown size={18} /><span>VIP club</span><ChevronRight size={14} /></button>
            </nav>
            <button className="nw-sidebar-promo" onClick={onOpenVip}><Crown size={30} /><span className="nw-eyebrow">THE INNER CIRCLE</span><strong>A little more VIP.<br />A lot more you.</strong><span>Explore your benefits <ChevronRight size={14} /></span></button>
            <div className="nw-sidebar-bottom"><Link className="nw-nav-link" href="/contact" onClick={() => setIsOpen(false)}><Headphones size={17} /><span>Help & support</span><i className="nw-live-dot" /></Link><Link href="/responsible-gaming" onClick={() => setIsOpen(false)}><ShieldCheck size={14} /> Play responsibly <b>18+</b></Link></div>
        </aside>
    );
}
