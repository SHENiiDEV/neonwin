import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { LockKeyhole, ArrowDownLeft, ArrowUpRight, ShieldCheck } from 'lucide-react';
const euro = n => new Intl.NumberFormat('en-IE', { style: 'currency', currency: 'EUR' }).format(Number(n || 0));
export default function VaultPanel() {
    const { auth } = usePage().props;
    const user = auth.user;
    const [action, setAction] = useState('deposit');
    const [amount, setAmount] = useState('');
    const [pin, setPin] = useState('');
    const [busy, setBusy] = useState(false);
    const [feedback, setFeedback] = useState(null);
    async function submit(e) {
        e.preventDefault(); setBusy(true); setFeedback(null);
        try {
            const { data } = await axios.post(`/vault/${action}`, action === 'set-pin' ? { pin } : { amount, pin });
            setFeedback({ success: true, message: data.message }); setAmount(''); setPin('');
            router.reload({ only: ['auth', 'stats'], preserveScroll: true });
        } catch (error) { setFeedback({ success: false, message: error.response?.data?.message || 'Unable to complete this request. Please try again.' }); }
        finally { setBusy(false); }
    }
    return <div className="grid xl:grid-cols-[1.1fr_1fr] gap-6">
        <section className="nw-account-card nw-vault-story"><span className="nw-eyebrow text-cyan-300">A LITTLE SPACE FROM PLAY</span><LockKeyhole size={60} strokeWidth={1} className="text-cyan-300 my-8" /><h2 className="text-3xl font-bold">Your balance.<br />On your terms.</h2><p className="text-gray-400 text-sm leading-7 mt-4 max-w-md">Move EUR out of your playable wallet and into your Cyber Vault. Bring it back whenever you choose, with an optional four-digit PIN.</p><div className="grid sm:grid-cols-2 gap-4 mt-8">{[['Playable wallet', user.game_balance], ['In your vault', user.vault_balance]].map(([label, value]) => <div key={label} className="rounded-2xl border border-white/10 p-5 bg-black/20"><span className="text-xs text-gray-400">{label}</span><strong className="block text-3xl mt-2 tabular-nums">{euro(value)}</strong></div>)}</div><p className="text-xs text-gray-500 mt-6 leading-6">Vault transfers stay within your Neonwin account. The vault does not earn interest or set a spending limit.</p></section>
        <section className="nw-account-card"><h2 className="text-xl font-bold">Manage Cyber Vault</h2><div className="flex flex-wrap gap-2 my-6">{[['deposit', 'Move to vault', ArrowDownLeft], ['withdraw', 'Return to wallet', ArrowUpRight], ['set-pin', 'PIN security', ShieldCheck]].map(([key, label, Icon]) => <button key={key} disabled={busy} onClick={() => { setAction(key); setAmount(''); setPin(''); setFeedback(null); }} aria-pressed={action === key} className={`rounded-xl px-4 py-3 text-xs flex gap-2 items-center ${action === key ? 'bg-cyan-300/15 text-cyan-200 border border-cyan-300/30' : 'bg-white/5 text-gray-400 border border-transparent'}`}><Icon size={15} />{label}</button>)}</div>
        <form onSubmit={submit} className="flex flex-col gap-5">
            {action !== 'set-pin' && <label className="nw-account-label">Amount in EUR<input type="number" min="0.01" step="0.01" max={action === 'deposit' ? user.game_balance : user.vault_balance} required value={amount} onChange={e => setAmount(e.target.value)} placeholder="0.00" className="nw-account-input" /><span className="text-xs text-gray-500">Available: {euro(action === 'deposit' ? user.game_balance : user.vault_balance)}</span></label>}
            {(action === 'set-pin' || (action === 'withdraw' && user.has_vault_pin)) && <label className="nw-account-label">{action === 'set-pin' ? 'New four-digit PIN' : 'Vault PIN'}<input type="password" inputMode="numeric" pattern="[0-9]{4}" maxLength={4} required value={pin} onChange={e => setPin(e.target.value)} autoComplete="off" className="nw-account-input" placeholder="••••" /></label>}
            {action === 'set-pin' && <p className="text-sm text-gray-400 leading-6">{user.has_vault_pin ? 'Replace your current vault PIN.' : 'Add a PIN to confirm every transfer from your vault back to your playable wallet.'}</p>}
            {feedback && <p role="status" className={`rounded-xl p-4 text-sm ${feedback.success ? 'bg-emerald-400/10 text-emerald-200' : 'bg-red-400/10 text-red-200'}`}>{feedback.message}</p>}
            <button disabled={busy} className="nw-button nw-button-purple self-start">{busy ? 'Processing…' : action === 'deposit' ? 'Move EUR to vault' : action === 'withdraw' ? 'Return EUR to wallet' : 'Save PIN'}</button>
        </form></section>
    </div>;
}
