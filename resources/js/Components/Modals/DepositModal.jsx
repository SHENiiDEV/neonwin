import React, { useEffect, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import axios from 'axios';
import { X, Wallet, ShieldCheck } from 'lucide-react';

export default function DepositModal({ isOpen, onClose }) {
    const { auth, payments } = usePage().props;
    const [selected, setSelected] = useState('eur_25');
    const [busy, setBusy] = useState(false);
    const [message, setMessage] = useState('');
    const [tab, setTab] = useState('deposit');
    useEffect(() => {
        if (!isOpen) return;
        const reference = new URLSearchParams(window.location.search).get('payment');
        if (!reference) return;
        let active = true;
        let timer;
        let attempts = 0;
        const checkStatus = async () => {
            try {
                const { data } = await axios.get(`/api/payments/orders/${encodeURIComponent(reference)}/status`);
                if (!active) return;
                if (data.status === 'confirmed') {
                    setMessage(`€${(data.amountCents / 100).toFixed(2)} was added to your EUR wallet.`);
                    router.reload({ only: ['auth'] });
                    return;
                }
                if (data.status === 'test_processed') {
                    setMessage('Test payment completed. Your EUR wallet was not credited.');
                    return;
                }
                if (data.status === 'failed') {
                    setMessage('Payment was not completed. Your EUR wallet was not credited.');
                    return;
                }
                setMessage('Payment is being confirmed. Your wallet will update after verification.');
            } catch {
                if (active) setMessage('Payment status is temporarily unavailable. Please check again shortly.');
            }
            if (active && attempts++ < 14) timer = window.setTimeout(checkStatus, 4000);
        };
        checkStatus();
        return () => { active = false; window.clearTimeout(timer); };
    }, [isOpen]);
    if (!isOpen) return null;
    const requestPayment = async () => {
        setBusy(true); setMessage('');
        try {
            const response = await axios.post('/deposit', { package: selected });
            window.location.assign(response.data.hppUrl);
        }
        catch (error) { setMessage(error.response?.data?.message || 'The payment page could not be opened. Try again later.'); }
        finally { setBusy(false); }
    };
    const withdrawal = tab === 'withdraw' && auth?.user?.kyc_approved;
    return <div className="fixed inset-0 z-[100] bg-black/75 backdrop-blur-sm flex items-center justify-center p-4" onClick={onClose}>
        <section role="dialog" aria-modal="true" aria-labelledby="cashier-title" className="w-full max-w-lg max-h-[90vh] overflow-y-auto rounded-3xl border border-[#3b324c] bg-[#14161f] shadow-2xl" onClick={e => e.stopPropagation()}>
            <div className="p-6 sm:p-8 border-b border-white/5 flex justify-between gap-4"><div><span className="nw-eyebrow text-purple-300">YOUR EURO WALLET</span><h2 id="cashier-title" className="text-3xl font-bold mt-2">Cashier</h2></div><button onClick={onClose} className="nw-icon-button self-start" aria-label="Close cashier"><X size={20} /></button></div>
            <div className="p-6 sm:p-8 flex flex-col gap-6"><div className="flex justify-between items-center"><span className="text-gray-400 text-sm">Available balance</span><strong className="text-2xl tabular-nums">€{Number(auth?.user?.game_balance || 0).toFixed(2)}</strong></div>
                <div className="flex gap-2"><button className={`nw-button ${!withdrawal ? 'nw-button-purple' : 'bg-white/5'}`} onClick={() => { setTab('deposit'); setMessage(''); }}>Deposit</button>{auth?.user?.kyc_approved && <button className={`nw-button ${withdrawal ? 'nw-button-purple' : 'bg-white/5'}`} onClick={() => { setTab('withdraw'); setMessage(''); }}>Withdraw</button>}</div>
                {withdrawal ? <div className="rounded-2xl border border-purple-400/20 bg-purple-500/5 p-6 flex flex-col gap-3"><ShieldCheck className="text-emerald-300" /><h3 className="text-xl font-bold">Withdrawals are coming soon</h3><p className="text-gray-400 text-sm">Your identity is verified. Payouts are not available yet. Your balance remains available in your wallet.</p></div> : <><div className="grid grid-cols-2 gap-3">{[10,25,50,100].map(amount => <button key={amount} onClick={() => setSelected(`eur_${amount}`)} aria-pressed={selected === `eur_${amount}`} className={`rounded-xl p-5 border text-left ${selected === `eur_${amount}` ? 'border-purple-400 bg-purple-500/10' : 'border-white/10 bg-white/[0.02]'}`}><span className="text-2xl font-bold">€{amount}</span><span className="block text-xs text-gray-400 mt-1">EUR deposit</span></button>)}</div><div className={`text-sm border rounded-xl p-4 ${payments?.enabled ? 'text-purple-100 bg-purple-400/5 border-purple-400/20' : 'text-amber-200 bg-amber-300/5 border-amber-300/15'}`}>{payments?.enabled ? payments.testMode ? 'Test checkout is enabled. Test payments will not add EUR to your wallet.' : 'Continue to Sterling Pay to complete your EUR deposit. Funds are added only after the payment is verified.' : 'Payments are currently unavailable. No funds will be taken.'}</div><button disabled={busy || !payments?.enabled} onClick={requestPayment} className="nw-button nw-button-purple"><Wallet size={16} />{busy ? 'Opening payment page…' : 'Continue to secure payment'}</button></>}
                {message && <p role="status" className="text-sm text-gray-300 break-words">{message}</p>}
                <Link href="/profile?section=verification" onClick={onClose} className="text-xs text-purple-300 flex items-center gap-2"><ShieldCheck size={14} />Identity verification · {auth?.user?.kyc_approved ? 'Approved' : 'Review required'}</Link>
            </div>
        </section>
    </div>;
}
