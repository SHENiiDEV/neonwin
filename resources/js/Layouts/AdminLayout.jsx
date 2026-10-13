import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Activity, ArrowLeft, BadgeEuro, Bell, BookOpen, ChevronRight, Crown, Gift, LayoutDashboard, Menu, Search, ShieldCheck, Users, Wallet, X } from 'lucide-react';

const sections = [
    { label: 'Workspace', links: [
        ['Dashboard', '/admin', LayoutDashboard], ['Alerts', '/admin?view=alerts', Bell], ['Audit log', '/admin?view=audit', BookOpen],
    ] },
    { label: 'Players & money', links: [
        ['Players', '/admin?view=users', Users], ['EUR wallets', '/admin/wallets', Wallet], ['Verification', '/admin?view=kyc', ShieldCheck], ['Deposits', '/admin?view=deposits', BadgeEuro], ['Wallet ledger', '/admin?view=ledger', BookOpen],
    ] },
    { label: 'Gaming', links: [
        ['Bets & wins', '/admin?view=transactions', Activity], ['Sessions', '/admin?view=sessions', Activity], ['Providers', '/admin?view=providers', Search], ['Provider requests', '/admin?view=rewards', ChevronRight],
    ] },
    { label: 'Rewards', links: [
        ['Promotions', '/admin/promotions', Gift], ['Daily wheel', '/admin/launch?section=wheel', Gift], ['Daily rakeback', '/admin/launch?section=rakeback', BadgeEuro], ['VIP & quests', '/admin/vip', Crown],
    ] },
    { label: 'Outreach', links: [['Registrations & Telegram', '/admin/launch?section=contacts', Users], ['Email previews', '/admin/emails', BookOpen]] },
];

function activeLink(current, href) {
    const [path, search] = href.split('?');
    const [currentPath, currentSearch] = current.split('?');
    if (path !== currentPath) return false;
    if (!search) return !currentSearch || path === '/admin/wallets' || path === '/admin/promotions' || path === '/admin/vip';
    const key = path === '/admin/launch' ? 'section' : 'view';
    return new URLSearchParams(currentSearch || '').get(key) === new URLSearchParams(search).get(key);
}

export default function AdminLayout({ children, title = 'Dashboard', description = '' }) {
    const { url, props } = usePage();
    const [open, setOpen] = useState(false);
    const current = url.split('#')[0];

    return <div className="nw-app min-h-screen bg-[#0d0f16] text-gray-100 font-sans lg:flex">
        <a href="#admin-content" className="nw-skip-link">Skip to administration content</a>
        {open && <button aria-label="Close menu" className="fixed inset-0 z-30 bg-black/70 lg:hidden" onClick={() => setOpen(false)} />}
        <aside className={`fixed inset-y-0 left-0 z-40 w-[270px] overflow-y-auto border-r border-white/10 bg-[#14121d] p-5 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 ${open ? 'translate-x-0' : '-translate-x-full'}`}>
            <div className="mb-8 flex items-start justify-between gap-3"><Link href="/admin" className="flex items-center gap-3"><span className="grid size-10 place-items-center rounded-xl bg-purple-500/20 text-purple-300"><ShieldCheck size={22} /></span><span><strong className="block text-lg tracking-tight">neonwin</strong><small className="block text-[10px] uppercase tracking-[0.22em] text-amber-200">Administration</small></span></Link><button className="lg:hidden" aria-label="Close navigation" onClick={() => setOpen(false)}><X size={20} /></button></div>
            <nav aria-label="Administration" className="space-y-7">{sections.map(section => <div key={section.label}><p className="mb-2 px-3 text-[10px] font-semibold uppercase tracking-[0.2em] text-gray-500">{section.label}</p><div className="space-y-1">{section.links.map(([label, href, Icon]) => <Link key={href} href={href} onClick={() => setOpen(false)} aria-current={activeLink(current, href) ? 'page' : undefined} className={`flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition-colors ${activeLink(current, href) ? 'bg-purple-500/20 font-semibold text-purple-100 ring-1 ring-purple-400/20' : 'text-gray-400 hover:bg-white/5 hover:text-white'}`}><Icon size={17} strokeWidth={1.8} />{label}</Link>)}</div></div>)}</nav>
            <Link href="/" className="mt-10 flex items-center gap-2 rounded-xl border border-white/10 px-3 py-3 text-sm text-gray-300 hover:border-purple-400/40 hover:text-white"><ArrowLeft size={16} /> Back to site</Link>
        </aside>
        <div className="min-w-0 flex-1"><header className="sticky top-0 z-20 flex min-h-16 items-center justify-between gap-4 border-b border-white/10 bg-[#0d0f16]/95 px-4 backdrop-blur-lg sm:px-8"><div className="flex items-center gap-3"><button className="rounded-lg border border-white/10 p-2 lg:hidden" aria-label="Open navigation" onClick={() => setOpen(true)}><Menu size={19} /></button><span className="text-xs font-semibold uppercase tracking-[0.18em] text-purple-300">Admin workspace</span></div><div className="truncate text-xs text-gray-400">{props.auth?.user?.email}</div></header>
            {props.flash?.success && <div role="status" className="mx-4 mt-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 p-4 text-sm text-emerald-200 sm:mx-8">{props.flash.success}</div>}
            {props.flash?.error && <div role="alert" className="mx-4 mt-5 rounded-xl border border-red-400/30 bg-red-400/10 p-4 text-sm text-red-200 sm:mx-8">{props.flash.error}</div>}
            <main id="admin-content" className="mx-auto w-full max-w-[1700px] px-4 py-7 sm:px-8"><div className="mb-7 border-b border-white/10 pb-6"><p className="mb-2 text-xs font-semibold uppercase tracking-[0.2em] text-amber-200">Operations / {title}</p><h1 className="text-3xl font-semibold tracking-tight sm:text-4xl">{title}</h1>{description && <p className="mt-3 max-w-3xl text-sm leading-6 text-gray-400">{description}</p>}</div>{children}</main>
        </div>
    </div>;
}
