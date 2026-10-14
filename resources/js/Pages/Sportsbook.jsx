import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Trophy, RefreshCw, ExternalLink } from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';

export default function Sportsbook({ launchUrl, message }) {
    const [loading, setLoading] = useState(Boolean(launchUrl));
    return <MainLayout><Head title="Sportsbook" />
        {launchUrl ? <section className="nw-sportsbook-shell" aria-label="Sportsbook">
            <a className="nw-sportsbook-mobile-action" href={launchUrl} target="_blank" rel="noopener noreferrer">Open sportsbook in full screen <ExternalLink size={16} /></a>
            {loading && <div role="status" className="nw-sportsbook-loading">Opening Sportsbook…</div>}
            <iframe title="Neonwin Sportsbook" src={launchUrl} onLoad={() => setLoading(false)} className="nw-sportsbook-frame" allow="fullscreen" referrerPolicy="strict-origin-when-cross-origin" />
        </section> : <div className="nw-sportsbook-unavailable"><div className="rounded-2xl border border-[#35303f] bg-[#14161f] p-8 sm:p-14 flex flex-col items-start gap-5"><Trophy className="text-purple-300" size={36} /><h1 className="text-2xl font-bold">Sportsbook is unavailable</h1><p role="alert" className="text-gray-400 max-w-xl">{message}</p><Link href="/sportsbook" className="nw-button nw-button-purple"><RefreshCw size={16} />Try again</Link><Link href="/" className="text-sm text-gray-400">Return to the casino lobby</Link></div></div>}
    </MainLayout>;
}
