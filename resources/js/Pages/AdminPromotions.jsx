import React from 'react';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Gift, ArrowLeft, Ticket } from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';

const inputClass = 'w-full rounded-xl border border-white/10 bg-[#0d0f16] p-3 text-sm text-white focus:border-purple-400 focus:outline-none';
const euro = cents => `€${(Number(cents || 0) / 100).toFixed(2)}`;
function Field({ label, error, children }) {
    return <label className="flex flex-col gap-2 text-xs text-gray-400">{label}{children}{error && <span role="alert" className="text-red-300">{error}</span>}</label>;
}
function PromotionForm({ promotion, games }) {
    const firstDeposit = promotion?.kind === 'first_deposit';
    const form = useForm({
        name: promotion?.name || '', code: promotion?.code || '', kind: promotion?.kind || 'deposit_bonus', enabled: promotion?.enabled ?? false,
        percent: (promotion?.percent_bps ?? 2000) / 100, min_deposit: (promotion?.min_deposit_cents ?? 0) / 100,
        max_bonus: promotion?.max_bonus_cents == null ? '' : promotion.max_bonus_cents / 100, redemption_limit: promotion?.redemption_limit || '',
        starts_at: promotion?.starts_at?.slice(0,16) || '', ends_at: promotion?.ends_at?.slice(0,16) || '',
        terms: promotion?.terms || 'First confirmed deposit only. Once per account. Deposit offers do not stack; the highest eligible bonus applies.',
        game_code: promotion?.reward?.game_code || '', bet_level: promotion?.reward?.bet_level || '', spin_count: promotion?.reward?.spin_count || 10,
        amount: promotion?.reward?.amount || '2.00', expiry_hours: promotion?.reward?.expiry_hours || 168,
    });
    const freeSpins = form.data.kind === 'free_spins';
    const game = games.find(game => game.game_code === form.data.game_code);
    const field = (name, label, type = 'text', props = {}) => <Field label={label} error={form.errors[name]}><input className={inputClass} type={type} value={form.data[name]} onChange={e => form.setData(name, e.target.value)} {...props} /></Field>;
    const submit = e => {
        e.preventDefault();
        form.transform(data => ({ ...data, starts_at: data.starts_at ? `${data.starts_at}:00Z` : null, ends_at: data.ends_at ? `${data.ends_at}:00Z` : null }));
        form.post(promotion ? `/admin/promotions/${promotion.id}` : '/admin/promotions', { preserveScroll: true, onSuccess: () => { if (!promotion) form.reset(); } });
    };
    return <form onSubmit={submit} className="flex flex-col gap-5">
        <div className="flex flex-wrap gap-4 items-center justify-between"><div><h2 className="text-xl font-bold">{firstDeposit ? 'First deposit · every player' : promotion ? promotion.name : 'Create a registration code'}</h2><p className="text-xs text-gray-500 mt-2">{firstDeposit ? 'Applied automatically after the first verified EUR payment.' : 'A player enters this code when creating their account.'}</p></div><label className="flex items-center gap-2 text-sm text-gray-300"><input type="checkbox" checked={form.data.enabled} onChange={e => form.setData('enabled', e.target.checked)} />Enabled</label></div>
        <div className="grid sm:grid-cols-2 gap-4">{field('name','Offer name','text',{required:true,maxLength:100})}{!firstDeposit && field('code','Promo code','text',{required:true,maxLength:40})}
            {!firstDeposit && <Field label="Reward type" error={form.errors.kind}><select className={inputClass} disabled={!!promotion} value={form.data.kind} onChange={e => form.setData('kind',e.target.value)}><option value="deposit_bonus">First deposit percentage</option><option value="free_spins">FreeSpins · PRAGMATIC</option></select></Field>}
            {!freeSpins && <>{field('percent','Deposit bonus %','number',{min:0,max:1000,step:'0.01',required:true})}{field('min_deposit','Minimum first deposit · EUR','number',{min:0,step:'0.01',required:true})}{field('max_bonus','Maximum bonus · EUR (blank = no cap)','number',{min:'0.01',step:'0.01'})}</>}
            {freeSpins && <><Field label="PRAGMATIC game" error={form.errors.game_code}><select required className={inputClass} value={form.data.game_code} onChange={e => {form.setData('game_code',e.target.value);form.setData('bet_level','');}}><option value="">Choose a synchronized game</option>{games.map(game => <option key={game.game_code} value={game.game_code}>{game.name}</option>)}</select></Field><Field label="Catalog bet level" error={form.errors.bet_level}><select required className={inputClass} value={form.data.bet_level} onChange={e => form.setData('bet_level',e.target.value)}><option value="">Choose a bet level</option>{Object.entries(game?.bet_levels || {}).map(([level,bet]) => <option key={level} value={level}>Level {level} · €{Number(bet).toFixed(2)}</option>)}</select></Field>{field('spin_count','Number of FreeSpins','number',{min:1,max:1000,required:true})}{field('amount','Nexus tour budget · EUR','number',{min:'0.01',step:'0.01',required:true})}{field('expiry_hours','Delivery / play window · hours','number',{min:1,max:720,required:true})}</>}
            {field('redemption_limit','Maximum reservations (blank = unlimited)','number',{min:1})}{field('starts_at','Starts at · UTC (optional)','datetime-local')}{field('ends_at','Ends at · UTC (optional)','datetime-local')}
        </div>
        <Field label="Player-facing offer terms" error={form.errors.terms}><textarea required rows={3} className={inputClass} value={form.data.terms} onChange={e => form.setData('terms',e.target.value)} /></Field>
        {freeSpins && <p className="text-xs text-gray-500">Nexus must have reward permission and sufficient agent funds. An unknown response keeps its original request ID and requires reconciliation. A tour refund does not credit a player wallet.</p>}
        <button disabled={form.processing} className="nw-button nw-button-purple self-start"><Gift size={16} />{form.processing ? 'Saving…' : promotion ? 'Save offer' : 'Create promo code'}</button>
    </form>;
}
export default function AdminPromotions({ promotions, redemptions, games, rewardDeliveryEnabled }) {
    return <MainLayout><Head title="Promotions administration" /><div className="py-6 flex flex-col gap-6 max-w-6xl mx-auto">
        <Link href="/admin" className="text-sm text-gray-400 inline-flex items-center gap-2"><ArrowLeft size={15} />Back to operations</Link>
        <header><span className="nw-eyebrow text-purple-300"><Ticket size={14} /> NEONWIN · PROMOTIONS</span><h1 className="text-3xl sm:text-4xl font-bold mt-3">A little more, on your terms.</h1><p className="text-sm text-gray-400 mt-3 max-w-2xl">Set the first deposit offer and create registration codes. Deposit bonuses apply once, after verified payment; the highest eligible bonus wins. New settings do not rewrite offers already reserved by a player.</p></header>
        <div className="rounded-xl border border-amber-300/20 bg-amber-300/5 p-4 text-xs text-amber-200">Payments are currently unavailable. These settings prepare future verified payments.{!rewardDeliveryEnabled && ' FreeSpins can be reserved; delivery is pending Nexus reward capability.'}</div>
        {promotions.filter(p => p.kind === 'first_deposit').map(p => <section key={p.id} className="rounded-2xl border border-purple-400/25 bg-[#181422] p-5 sm:p-7"><PromotionForm promotion={p} games={games} /></section>)}
        <section className="rounded-2xl border border-white/10 bg-[#14161f] p-5 sm:p-7"><PromotionForm games={games} /></section>
        <section className="flex flex-col gap-4"><h2 className="text-xl font-bold">Registration promo codes</h2>{promotions.filter(p => p.kind !== 'first_deposit').map(p => <details key={p.id} className="rounded-2xl border border-white/10 bg-[#14161f] p-5 sm:p-7"><summary className="cursor-pointer flex flex-wrap items-center gap-3"><Ticket size={18} className="text-purple-300" /><strong className="font-mono text-sm">{p.code}</strong><span className="text-sm text-gray-400">{p.name}</span><span className="text-xs ml-auto text-gray-500">{p.enabled ? 'Enabled' : 'Disabled'} · {p.redemptions_count} reserved</span></summary><div className="pt-6"><PromotionForm promotion={p} games={games} /></div></details>)}{promotions.length === 1 && <p className="text-sm text-gray-500">Create your first code above.</p>}</section>
        <section className="rounded-2xl border border-white/10 bg-[#14161f] p-5 sm:p-7"><h2 className="text-xl font-bold mb-5">Reservations & delivery</h2>{redemptions.data.length ? <div className="overflow-x-auto"><table className="w-full text-left text-xs min-w-[640px]"><thead className="text-gray-500"><tr>{['Player','Offer','State','Bonus','Delivery'].map(label=><th key={label} className="p-3">{label}</th>)}</tr></thead><tbody>{redemptions.data.map(r=><tr key={r.id} className="border-t border-white/5 align-top"><td className="p-3">{r.user?.email}<span className="block text-gray-500 mt-1">{r.user?.user_code}</span></td><td className="p-3">{r.snapshot.name}<span className="block font-mono text-purple-300 mt-1">{r.snapshot.code || 'Every player'}</span></td><td className="p-3">{r.status}</td><td className="p-3">{euro(r.bonus_cents)}</td><td className="p-3 max-w-sm"><p className="text-gray-400">{r.message || 'Reserved for the first verified deposit.'}</p>{r.snapshot.kind === 'free_spins' && <><span className="block text-gray-600 font-mono mt-2 break-all">{r.request_id}</span><button className="text-purple-300 mt-3" onClick={()=>router.post(`/admin/promotion-redemptions/${r.id}/issue`,{}, {preserveScroll:true})}>Check / deliver FreeSpins</button></>}</td></tr>)}</tbody></table></div> : <p className="text-sm text-gray-500">Reservations appear after a player registers with a code or receives a first deposit bonus.</p>}<div className="flex gap-4 mt-5 text-sm text-purple-300">{redemptions.prev_page_url && <Link href={redemptions.prev_page_url}>Previous</Link>}{redemptions.next_page_url && <Link href={redemptions.next_page_url}>Next</Link>}</div></section>
    </div></MainLayout>;
}
