import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Trophy, RefreshCw } from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';

export default function Sportsbook({ launchUrl, message }) {
    const [loading, setLoading] = useState(Boolean(launchUrl));
    return <MainLayout><Head title="Sportsbook" /><div className="flex flex-col gap-5 py-4"><div className="flex items-center justify-between gap-4"><div><span className="nw-eyebrow text-purple-300">SPORTS · YOUR EUR WALLET</span><h1 className="text-3xl font-bold mt-2">Every match. Your arena.</h1></div><Trophy size={32} className="text-amber-200" /></div>
        {launchUrl ? <div className="relative rounded-2xl overflow-hidden border border-white/10 bg-[#14161f]">{loading && <div role="status" className="absolute top-4 left-4 rounded-lg bg-[#14161f] p-3 text-sm">Opening Sportsbook…</div>}<iframe title="Neonwin Sportsbook" src={launchUrl} onLoad={() => setLoading(false)} className="w-full h-[75vh] min-h-[500px]" allow="fullscreen" referrerPolicy="strict-origin-when-cross-origin" /></div> : <div className="rounded-2xl border border-[#35303f] bg-[#14161f] p-8 sm:p-14 flex flex-col items-start gap-5"><Trophy className="text-purple-300" size={36} /><h2 className="text-2xl font-bold">Sportsbook is unavailable</h2><p role="alert" className="text-gray-400 max-w-xl">{message}</p><Link href="/sportsbook" className="nw-button nw-button-purple"><RefreshCw size={16} />Try again</Link><Link href="/" className="text-sm text-gray-400">Return to the casino lobby</Link></div>}
    </div></MainLayout>;
}
