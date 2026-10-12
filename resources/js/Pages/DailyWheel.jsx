import React, { useEffect, useRef, useState } from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowUpRight, Clock3, Gift, ShieldCheck, Sparkles } from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';
import { Brand } from '../Components/Sidebar';
const euro = cents => new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(cents / 100);
const point = (radius, angle) => [250 + radius * Math.cos(angle * Math.PI / 180), 250 + radius * Math.sin(angle * Math.PI / 180)];
export default function DailyWheel({ prizes, available, endsAt, nextSpinAt, history, result }) {
    const { auth } = usePage().props;
    const [now, setNow] = useState(Date.now());
    const [rotation, setRotation] = useState(0);
    const [spinning, setSpinning] = useState(false);
    const [shown, setShown] = useState(null);
    const [sectors, setSectors] = useState(prizes);
    const seen = useRef(null);
    const form = useForm({ request_id: crypto.randomUUID() });
    useEffect(() => { const timer = setInterval(() => setNow(Date.now()), 1000); return () => clearInterval(timer); }, []);
    useEffect(() => { if (!spinning) setSectors(prizes); }, [prizes, spinning]);
    useEffect(() => {
        if (!result || result.id === seen.current) return;
        seen.current = result.id;
        setSectors(result.prizes);
        setSpinning(true);
        setShown(null);
        const target = (360 - (result.prize_index + 0.5) * 45) % 360;
        const frame = requestAnimationFrame(() => setRotation(old => Math.ceil(old / 360) * 360 + 1800 + target));
        const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const timer = setTimeout(() => { setSpinning(false); setShown(result); form.setData('request_id', crypto.randomUUID()); }, reduced ? 50 : 5300);
        return () => { cancelAnimationFrame(frame); clearTimeout(timer); };
    }, [result?.id]);
    const remaining = Math.max(0, Math.ceil((new Date(nextSpinAt || 0).getTime() - now) / 1000));
    const cooldown = [Math.floor(remaining / 3600), Math.floor(remaining % 3600 / 60), remaining % 60].map(n => String(n).padStart(2, '0')).join(':');
    const total = prizes.reduce((sum, item) => sum + item.weight, 0);
    return <MainLayout>{({ openAuth }) => <div className="nw-wheel-page pb-10"><Head title="Daily wheel · A little daily luck" />
        <header className="flex flex-wrap items-center justify-between gap-4 mb-7"><span className="nw-eyebrow !mb-0 text-amber-200"><Sparkles size={14} /> THE DAILY DROP</span><Link href="/vip" className="text-sm text-purple-300 flex items-center gap-2">Explore VIP club <ArrowUpRight size={15} /></Link></header>
        <section className="nw-wheel-stage"><div className="nw-wheel-copy"><span className="nw-wheel-edition">01 / YOUR DAILY MOMENT</span><h1>A little spin.<br /><em>A little extra.</em></h1><p>One free spin every 24 hours. Every slice holds a EUR reward, sent straight to your play wallet.</p><div className="nw-wheel-facts"><span><Clock3 size={16} /> Every 24 hours</span><span><Gift size={16} /> No deposit required</span></div><div className="nw-wheel-action">
            {!auth.user ? <button className="nw-button nw-button-purple w-full" onClick={() => openAuth('register')}>Create account to spin <ArrowUpRight size={17} /></button> : <button disabled={!available || remaining > 0 || form.processing || spinning} className="nw-button nw-button-purple w-full" onClick={() => { setShown(null); form.post('/wheel/spin', { preserveScroll: true }); }}>{form.processing ? 'Choosing your reward…' : spinning ? 'Your daily moment…' : remaining > 0 ? `Next spin in ${cooldown}` : available ? 'Spin my daily wheel' : 'The wheel is paused'}<Sparkles size={17} /></button>}
            <div role="status" aria-live="polite" className="nw-wheel-result">{shown ? <><strong>+{euro(shown.amount_cents)}</strong><span>Added to your EUR wallet. See you tomorrow.</span></> : <span>{!available ? 'We’re preparing the next daily drop. Check back soon.' : remaining > 0 ? 'Your daily reward is collected. Come back when the timer ends.' : 'Your reward is chosen securely before the animation begins.'}</span>}</div>
            {Object.values(form.errors).map((error, i) => <p role="alert" className="text-red-300 text-sm mt-2" key={i}>{error}</p>)}
        </div><p className="nw-wheel-fineprint">18+ · One account per person. Play within your limits.<br />{endsAt && `Available until ${new Date(endsAt).toLocaleString('en-GB', { timeZone: 'UTC' })} UTC.`}</p></div>
        <div className="nw-wheel-art"><span className="nw-wheel-orbit nw-wheel-orbit-one" /><span className="nw-wheel-orbit nw-wheel-orbit-two" /><span className="nw-wheel-caption">A SMALL REWARD. ALL YOURS.</span><div className="nw-wheel-machine"><div className="nw-wheel-pointer" /><svg viewBox="0 0 500 500" role="img" aria-label="Daily EUR prize wheel" className="nw-wheel-disc" style={{ transform: `rotate(${rotation}deg)` }}><circle cx="250" cy="250" r="247" fill="#1a1427" stroke="#d3ac58" strokeWidth="2" /><circle cx="250" cy="250" r="233" fill="#352345" />{sectors.map((prize, i) => { const start = -90 + i * 45; const end = start + 45; const a = point(224, start); const b = point(224, end); const label = point(155, start + 22.5); return <g key={i}><path d={`M250 250 L${a.join(' ')} A224 224 0 0 1 ${b.join(' ')} Z`} fill={i % 2 ? '#352047' : '#5a3281'} stroke="#b899d0" strokeOpacity=".24" /><text x={label[0]} y={label[1]} textAnchor="middle" dominantBaseline="middle" transform={`rotate(${start + 112.5},${label.join(',')})`} fill={i === 7 ? '#ffe3a0' : '#f5edf9'} fontSize={euro(prize.cents).length > 7 ? 20 : 25} fontWeight="700">{euro(prize.cents)}</text></g>; })}{Array.from({ length: 32 }, (_, i) => { const p = point(239, i * 11.25); return <circle key={i} cx={p[0]} cy={p[1]} r="2.4" fill="#f4d48d" />; })}<circle cx="250" cy="250" r="71" fill="#18121f" stroke="#b18b4e" strokeWidth="2" /></svg><div className="nw-wheel-hub"><Brand /><small>DAILY WHEEL</small></div></div><span className="nw-wheel-art-foot">EUR REWARDS / EVERY DAY</span></div></section>
        <div className="grid xl:grid-cols-[1.35fr_1fr] gap-6 mt-6"><section className="nw-account-card"><div className="flex justify-between gap-4 items-center"><h2 className="text-xl font-semibold">Every prize. Every chance.</h2><ShieldCheck size={20} className="text-purple-300" /></div><p className="text-xs text-gray-400 leading-6 mt-3">The wheel shows equal-sized slices with different winning chances. All odds are listed below. Prizes are credited automatically; no wagering is required to receive them.</p><div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-5">{prizes.map((prize, i) => <div key={i} className="rounded-xl border border-white/10 p-4 bg-white/[.02]"><strong className="text-lg text-amber-100 tabular-nums">{euro(prize.cents)}</strong><span className="block text-xs text-gray-400 mt-2 tabular-nums">{(prize.weight / total * 100).toFixed(2)}% chance</span></div>)}</div><p className="text-xs text-gray-500 leading-6 mt-4">The wheel pauses if the reward fund cannot cover the highest prize. A paused wheel does not use your spin. Rewards do not enable withdrawals, which are currently unavailable. <Link href="/promotions" className="text-purple-300">Promotion rules</Link></p></section><section className="nw-account-card"><h2 className="text-xl font-semibold">Your daily drops</h2><p className="text-xs text-gray-500 mt-3">Your ten most recent spins. Times shown in UTC.</p>{history.length ? <ul className="mt-5 divide-y divide-white/5">{history.map(spin => <li className="py-4 flex items-center justify-between gap-3" key={spin.id}><span className="text-xs text-gray-400">{new Date(spin.created_at).toLocaleString('en-GB', { timeZone: 'UTC' })}</span><strong className="text-emerald-300 tabular-nums">+{euro(spin.amount_cents)}</strong></li>)}</ul> : <div className="py-12 text-center text-gray-500 text-sm"><Gift size={28} className="mx-auto text-purple-300 mb-4" />Your first daily drop belongs here.</div>}</section></div>
    </div>}</MainLayout>;
}
