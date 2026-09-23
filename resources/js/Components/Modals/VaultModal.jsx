import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { X, ShieldCheck, Lock, Unlock, ArrowDownRight, ArrowUpRight, Key, Coins, AlertCircle, CheckCircle2, LoaderCircle } from 'lucide-react';
import axios from 'axios';
import { playCoinSound } from '../../utils/soundEffects';

export default function VaultModal({ isOpen, onClose }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const soundEnabled = user?.sound_enabled !== false;

    const [activeTab, setActiveTab] = useState('deposit'); // 'deposit' | 'withdraw' | 'security'
    const [amount, setAmount] = useState('');
    const [pin, setPin] = useState('');
    const [newPin, setNewPin] = useState('');
    const [processing, setProcessing] = useState(false);
    const [feedback, setFeedback] = useState(null);

    if (!isOpen) return null;

    const handleDeposit = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setFeedback(null);
        try {
            const res = await axios.post('/vault/deposit', { amount });
            setFeedback({ type: 'success', message: res.data.message });
            setAmount('');
            playCoinSound(soundEnabled);
            router.reload({ only: ['auth'] });
        } catch (err) {
            setFeedback({ type: 'error', message: err.response?.data?.message || 'Deposit to vault failed.' });
        } finally {
            setProcessing(false);
        }
    };

    const handleWithdraw = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setFeedback(null);
        try {
            const res = await axios.post('/vault/withdraw', { amount, pin });
            setFeedback({ type: 'success', message: res.data.message });
            setAmount('');
            setPin('');
            playCoinSound(soundEnabled);
            router.reload({ only: ['auth'] });
        } catch (err) {
            setFeedback({ type: 'error', message: err.response?.data?.message || 'Withdrawal from vault failed.' });
        } finally {
            setProcessing(false);
        }
    };

    const handleSetPin = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setFeedback(null);
        try {
            const res = await axios.post('/vault/set-pin', { pin: newPin });
            setFeedback({ type: 'success', message: res.data.message });
            setNewPin('');
            router.reload({ only: ['auth'] });
        } catch (err) {
            setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to set PIN.' });
        } finally {
            setProcessing(false);
        }
    };

    const formatCoins = (val) => Number(val || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    return (
        <div className="nw-topup-backdrop" onClick={e => e.target === e.currentTarget && !processing && onClose()}>
            <section className="relative w-full max-w-xl rounded-3xl bg-[#130e21] border border-[#3f2c53] p-6 sm:p-8 shadow-[0_20px_80px_rgba(0,0,0,0.9)] overflow-hidden text-white animate-in zoom-in-95 duration-200">
                <button onClick={onClose} className="absolute top-5 right-5 p-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition">
                    <X size={18} />
                </button>

                {/* Header */}
                <div className="flex items-center gap-3.5 mb-6">
                    <div className="w-12 h-12 rounded-2xl bg-cyan-950/60 border border-cyan-500/40 flex items-center justify-center text-cyan-300 shadow-[0_0_20px_rgba(6,182,212,0.3)]">
                        <Lock size={22} />
                    </div>
                    <div>
                        <h2 className="font-heading font-black text-xl sm:text-2xl text-white">Cyber Vault (Piggy Bank)</h2>
                        <p className="text-xs text-gray-400">Lock and protect your SC bankroll from emotional play</p>
                    </div>
                </div>

                {/* Balances Display */}
                <div className="grid grid-cols-2 gap-3.5 mb-6">
                    <div className="p-4 rounded-2xl bg-[#1a1329] border border-[#3c2a4e]">
                        <span className="text-[10px] text-gray-400 uppercase font-bold tracking-wider">Playable Wallet</span>
                        <div className="font-heading font-black text-xl text-yellow-300 mt-1">
                            {formatCoins(user?.game_balance)} <small className="text-xs text-yellow-500 font-mono">SC</small>
                        </div>
                    </div>

                    <div className="p-4 rounded-2xl bg-cyan-950/30 border border-cyan-500/30">
                        <span className="text-[10px] text-cyan-300 uppercase font-bold tracking-wider">Vault Protected</span>
                        <div className="font-heading font-black text-xl text-cyan-300 mt-1">
                            {formatCoins(user?.vault_balance)} <small className="text-xs text-cyan-400 font-mono">SC</small>
                        </div>
                    </div>
                </div>


                {/* Tabs */}
                <div className="flex items-center gap-2 p-1 bg-[#191326] rounded-xl border border-[#352548] mb-5 text-xs font-bold">
                    <button
                        onClick={() => { setActiveTab('deposit'); setFeedback(null); }}
                        className={`flex-1 py-2 rounded-lg transition flex items-center justify-center gap-1.5 ${
                            activeTab === 'deposit' ? 'bg-purple-600 text-white shadow' : 'text-gray-400 hover:text-white'
                        }`}
                    >
                        <ArrowDownRight size={14} /> Deposit to Vault
                    </button>
                    <button
                        onClick={() => { setActiveTab('withdraw'); setFeedback(null); }}
                        className={`flex-1 py-2 rounded-lg transition flex items-center justify-center gap-1.5 ${
                            activeTab === 'withdraw' ? 'bg-purple-600 text-white shadow' : 'text-gray-400 hover:text-white'
                        }`}
                    >
                        <ArrowUpRight size={14} /> Withdraw to Play
                    </button>
                    <button
                        onClick={() => { setActiveTab('security'); setFeedback(null); }}
                        className={`flex-1 py-2 rounded-lg transition flex items-center justify-center gap-1.5 ${
                            activeTab === 'security' ? 'bg-purple-600 text-white shadow' : 'text-gray-400 hover:text-white'
                        }`}
                    >
                        <Key size={14} /> PIN Security
                    </button>
                </div>

                {/* Feedback Alerts */}
                {feedback && (
                    <div className={`p-3.5 rounded-xl text-xs font-bold mb-4 flex items-center gap-2 ${
                        feedback.type === 'success' 
                            ? 'bg-emerald-950/80 border border-emerald-500/40 text-emerald-300' 
                            : 'bg-red-950/80 border border-red-500/40 text-red-300'
                    }`}>
                        {feedback.type === 'success' ? <CheckCircle2 size={16} /> : <AlertCircle size={16} />}
                        <span>{feedback.message}</span>
                    </div>
                )}

                {/* Tab: Deposit */}
                {activeTab === 'deposit' && (
                    <form onSubmit={handleDeposit} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Amount to Lock (SC)</label>
                            <div className="relative">
                                <input
                                    type="number"
                                    step="any"
                                    min="0.01"
                                    max={user?.game_balance || 0}
                                    value={amount}
                                    onChange={e => setAmount(e.target.value)}
                                    placeholder="e.g. 50.00"
                                    required
                                    className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-cyan-500 font-mono"
                                />
                                <button
                                    type="button"
                                    onClick={() => setAmount(Number(user?.game_balance || 0).toFixed(2))}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-yellow-300"
                                >
                                    MAX
                                </button>
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={processing || !amount || Number(amount) <= 0}
                            className="w-full py-3 rounded-xl bg-gradient-to-r from-cyan-500 to-blue-600 hover:from-cyan-400 hover:to-blue-500 text-black font-heading font-black text-xs uppercase tracking-wider shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            {processing ? <LoaderCircle size={16} className="animate-spin" /> : <>Lock in Vault <Lock size={15} /></>}
                        </button>
                    </form>
                )}

                {/* Tab: Withdraw */}
                {activeTab === 'withdraw' && (
                    <form onSubmit={handleWithdraw} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Amount to Release (SC)</label>
                            <div className="relative">
                                <input
                                    type="number"
                                    step="any"
                                    min="0.01"
                                    max={user?.vault_balance || 0}
                                    value={amount}
                                    onChange={e => setAmount(e.target.value)}
                                    placeholder="e.g. 25.00"
                                    required
                                    className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-purple-500 font-mono"
                                />
                                <button
                                    type="button"
                                    onClick={() => setAmount(Number(user?.vault_balance || 0).toFixed(2))}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 px-2.5 py-1 rounded-lg bg-white/10 hover:bg-white/20 text-[10px] font-bold text-cyan-300"
                                >
                                    MAX
                                </button>
                            </div>
                        </div>


                        {user?.has_vault_pin && (
                            <div>
                                <label className="block text-xs font-bold text-gray-300 mb-1.5">4-Digit Security PIN</label>
                                <input
                                    type="password"
                                    maxLength={4}
                                    pattern="[0-9]{4}"
                                    value={pin}
                                    onChange={e => setPin(e.target.value)}
                                    placeholder="••••"
                                    required
                                    className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-center text-white tracking-[0.5em] focus:outline-none focus:border-purple-500 font-mono"
                                />
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing || !amount || Number(amount) <= 0}
                            className="w-full py-3 rounded-xl bg-gradient-to-r from-purple-500 to-pink-500 hover:from-purple-400 hover:to-pink-400 text-white font-heading font-black text-xs uppercase tracking-wider shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            {processing ? <LoaderCircle size={16} className="animate-spin" /> : <>Unlock to Wallet <Unlock size={15} /></>}
                        </button>
                    </form>
                )}

                {/* Tab: PIN Security */}
                {activeTab === 'security' && (
                    <form onSubmit={handleSetPin} className="space-y-4">
                        <p className="text-xs text-gray-300">
                            Set a 4-digit PIN to prevent impulsive withdrawals while gaming.
                        </p>

                        <div>
                            <label className="block text-xs font-bold text-gray-300 mb-1.5">Enter New 4-Digit PIN</label>
                            <input
                                type="password"
                                maxLength={4}
                                pattern="[0-9]{4}"
                                value={newPin}
                                onChange={e => setNewPin(e.target.value)}
                                placeholder="••••"
                                required
                                className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-center text-white tracking-[0.5em] focus:outline-none focus:border-cyan-500 font-mono"
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={processing || newPin.length !== 4}
                            className="w-full py-3 rounded-xl bg-cyan-600 hover:bg-cyan-500 text-black font-heading font-black text-xs uppercase tracking-wider shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50"
                        >
                            {processing ? <LoaderCircle size={16} className="animate-spin" /> : <>Save Vault PIN <ShieldCheck size={15} /></>}
                        </button>
                    </form>
                )}
            </section>
        </div>
    );
}
