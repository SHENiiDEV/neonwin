import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { CircleUserRound, Crown, Gift, House, LogIn, Trophy, Wallet } from 'lucide-react';

function NavItem({ href, onClick, icon: Icon, label, active = false, featured = false }) {
    const className = `nw-mobile-nav-item${active ? ' is-active' : ''}${featured ? ' is-featured' : ''}`;
    const content = <><span className="nw-mobile-nav-icon"><Icon size={21} strokeWidth={1.9} /></span><span>{label}</span></>;

    return href ? (
        <Link href={href} className={className} aria-current={active ? 'page' : undefined}>{content}</Link>
    ) : (
        <button type="button" onClick={onClick} className={className}>{content}</button>
    );
}

export default function MobileBottomNav({ onOpenAuth, onOpenDeposit }) {
    const { auth } = usePage().props;
    const pathname = usePage().url.split('?')[0];
    const signedIn = Boolean(auth?.user);

    return <nav className="nw-mobile-bottom-nav" aria-label="Quick navigation">
        <NavItem href="/" icon={House} label="Casino" active={pathname === '/' || pathname.startsWith('/game/')} />
        <NavItem href="/sportsbook" icon={Trophy} label="Sports" active={pathname === '/sportsbook'} />
        <NavItem href="/wheel" icon={Gift} label="Wheel" active={pathname === '/wheel'} featured />
        {signedIn ? <>
            <NavItem onClick={onOpenDeposit} icon={Wallet} label="Cashier" />
            <NavItem href="/profile" icon={CircleUserRound} label="Profile" active={pathname === '/profile'} />
        </> : <>
            <NavItem href="/vip" icon={Crown} label="VIP" active={pathname === '/vip'} />
            <NavItem onClick={() => onOpenAuth('login')} icon={LogIn} label="Sign in" />
        </>}
    </nav>;
}
