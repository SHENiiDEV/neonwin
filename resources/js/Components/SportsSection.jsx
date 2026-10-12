import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Trophy, ArrowUpRight } from 'lucide-react';
export default function SportsSection() {
    const { auth } = usePage().props;
    return <section className="rounded-2xl border border-[#30273e] bg-[#14161f] p-6 sm:p-8 my-8 flex flex-wrap justify-between items-center gap-6"><div className="flex items-center gap-5"><Trophy className="text-amber-200" size={34} /><div><span className="nw-eyebrow text-purple-300">SPORTSBOOK</span><h2 className="text-2xl font-bold mt-1">Find your next match.</h2><p className="text-sm text-gray-400 mt-2">Explore events and markets in your EUR account.</p></div></div><Link href="/sportsbook" onClick={e => { if (!auth?.user) { e.preventDefault(); window.dispatchEvent(new CustomEvent('open-auth-modal', { detail: { mode: 'login' } })); } }} className="nw-button nw-button-purple">Open Sportsbook <ArrowUpRight size={16} /></Link></section>;
}
