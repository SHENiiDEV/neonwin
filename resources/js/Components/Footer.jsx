import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Brand } from './Sidebar';

export default function Footer() {
    const { app_name, company } = usePage().props;
    const brandName = app_name || 'Neonwin';
    const operatorName = company?.name || brandName;
    const contactEmail = company?.email || 'info@Neonwin.co.uk';
    return <footer className="nw-footer"><div className="nw-footer-inner">
        <div className="nw-footer-top"><div className="nw-footer-brand"><Link href="/" aria-label="Neonwin home"><Brand /></Link><p>Your world of play. Discover new favorites, share good moments and enjoy a little neon magic.</p></div>
            <div className="nw-footer-links"><h3>Explore</h3><Link href="/?category=slots">Slots</Link><Link href="/?category=live">Live casino</Link><Link href="/?category=crash">Crash & fast games</Link><Link href="/vip">VIP club</Link></div>
            <div className="nw-footer-links"><h3>Here to help</h3><Link href="/about">About Neonwin</Link><Link href="/faq">Help center</Link><Link href="/contact">Contact support</Link><Link href="/responsible-gaming">Responsible play</Link></div>
            <div className="nw-footer-links"><h3>The essentials</h3><Link href="/terms">Terms & conditions</Link><Link href="/privacy">Privacy policy</Link><Link href="/promotions">Promotions & bonuses</Link></div>
        </div>
        <div className="nw-payments"><span>EUR WALLET · PAYMENT PROCESSING NOT YET AVAILABLE</span></div>
        <div className="nw-legal"><Link className="nw-age" href="/responsible-gaming" aria-label="Responsible gaming, adults 18 and over">18+</Link><div className="flex flex-col gap-2">
            <p><strong className="font-semibold text-[#bba6d0]">{brandName}</strong> is owned and operated by {operatorName}{company?.number ? `, registration number: ${company.number}` : ''}{company?.address ? `, registered address: ${company.address}` : ''}. Contact us at <a className="text-[#bba6d0] hover:text-white" href={`mailto:${contactEmail}`}>{contactEmail}</a>.</p>
            <p>The primary wallet uses EUR. Payment processing and withdrawals are currently unavailable. Previous virtual balances are archived without conversion. For adults aged 18+ in permitted jurisdictions. Play for entertainment and stay within your limits.</p>
        </div></div>
        <div className="nw-copyright"><span>© {new Date().getFullYear()} {brandName}. All rights reserved.</span><Link href="/responsible-gaming">Good times start with responsible play.</Link></div>
    </div></footer>;
}
