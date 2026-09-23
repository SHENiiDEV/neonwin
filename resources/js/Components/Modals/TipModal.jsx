import React, { useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import { X, Send, User, Coins, ShieldAlert, CheckCircle2, AlertCircle, LoaderCircle, Percent } from 'lucide-react';
import axios from 'axios';
import { playCoinSound } from '../../utils/soundEffects';

export default function TipModal({ isOpen, onClose }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const soundEnabled = user?.sound_enabled !== false;

    const [recipientCode, setRecipientCode] = useState('');
    const [amount, setAmount] = useState('');
    const [note, setNote] = useState('');
    const [processing, setProcessing] = useState(false);
    const [feedback, setFeedback] = useState(null);

    if (!isOpen) return null;

    const numAmount = Number(amount) || 0;
    const fee = Math.round(numAmount * 0.03);
    const netDelivered = Math.max(0, numAmount - fee);

    const handleSendTip = async (e) => {
        e.preventDefault();
        setProcessing(true);
        setFeedback(null);
        try {
            const res = await axios.post('/tipping/send', {
                recipient_code: recipientCode,
                amount: numAmount,
                note,
            });
            setFeedback({ type: 'success', message: res.data.message });
            setAmount('');
            setRecipientCode('');
            setNote('');
            playCoinSound(soundEnabled);
            router.reload({ only: ['auth'] });
        } catch (err) {
            setFeedback({ type: 'error', message: err.response?.data?.message || 'Failed to send tip.' });
        } finally {
            setProcessing(false);
        }
    };

    return (
        <div className="nw-topup-backdrop" onClick={e => e.target === e.currentTarget && !processing && onClose()}>
            <section className="relative w-full max-w-lg rounded-3xl bg-[#130e21] border border-[#3f2c53] p-6 sm:p-8 shadow-[0_20px_80px_rgba(0,0,0,0.9)] overflow-hidden text-white animate-in zoom-in-95 duration-200">
                <button onClick={onClose} className="absolute top-5 right-5 p-2 rounded-xl bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white transition">
                    <X size={18} />
                </button>

                {/* Header */}
                <div className="flex items-center gap-3.5 mb-6">
                    <div className="w-12 h-12 rounded-2xl bg-pink-950/60 border border-pink-500/40 flex items-center justify-center text-pink-300 shadow-[0_0_20px_rgba(236,72,153,0.3)]">
                        <Send size={22} />
                    </div>
                    <div>
                        <h2 className="font-heading font-black text-xl sm:text-2xl text-white">Player-to-Player Tip</h2>
                        <p className="text-xs text-gray-400">Instantly transfer Coins to a friend (3% platform fee)</p>
                    </div>
                </div>

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

                <form onSubmit={handleSendTip} className="space-y-4">
                    <div>
                        <label className="block text-xs font-bold text-gray-300 mb-1.5">Recipient User Code or Nickname</label>
                        <div className="relative">
                            <input
                                type="text"
                                value={recipientCode}
                                onChange={e => setRecipientCode(e.target.value)}
                                placeholder="e.g. NW-A1B2C3D4"
                                required
                                className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-pink-500 uppercase font-mono"
                            />
                        </div>
                    </div>

                    <div>
                        <div className="flex items-center justify-between mb-1.5">
                            <label className="text-xs font-bold text-gray-300">Amount to Send (Coins)</label>
                            <span className="text-[10px] text-gray-400">Available: {Math.floor(Number(user?.game_balance || 0)).toLocaleString()} Coins</span>
                        </div>
                        <input
                            type="number"
                            step="10000"
                            min="100000"
                            max={user?.game_balance || 0}
                            value={amount}
                            onChange={e => setAmount(e.target.value)}
                            placeholder="e.g. 500,000"
                            required
                            className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-3 text-xs text-white focus:outline-none focus:border-pink-500 font-mono"
                        />
                    </div>

                    <div>
                        <label className="block text-xs font-bold text-gray-300 mb-1.5">Note / Message (Optional)</label>
                        <input
                            type="text"
                            maxLength={100}
                            value={note}
                            onChange={e => setNote(e.target.value)}
                            placeholder="e.g. Thanks for the lucky spin advice!"
                            className="w-full bg-[#1b1428] border border-[#3c2a4f] rounded-xl px-4 py-2.5 text-xs text-white focus:outline-none focus:border-pink-500"
                        />
                    </div>

                    {/* Breakdown */}
                    {numAmount > 0 && (
                        <div className="p-3.5 rounded-2xl bg-[#1a1329] border border-[#3b294d] text-xs space-y-1.5 font-mono">
                            <div className="flex justify-between text-gray-400">
                                <span>Tip Amount:</span>
                                <span className="text-white">{Math.floor(numAmount).toLocaleString()} Coins</span>
                            </div>
                            <div className="flex justify-between text-gray-400">
                                <span className="flex items-center gap-1">Commission (3%):</span>
                                <span className="text-pink-400">-{Math.floor(fee).toLocaleString()} Coins</span>
                            </div>
                            <div className="border-t border-white/5 pt-1.5 flex justify-between font-bold text-emerald-400 text-sm">
                                <span>Delivered to Player:</span>
                                <span>{Math.floor(netDelivered).toLocaleString()} Coins</span>
                            </div>
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={processing || numAmount < 100000 || !recipientCode}
                        className="w-full py-3 rounded-xl bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-400 hover:to-purple-500 text-white font-heading font-black text-xs uppercase tracking-wider shadow-lg transition flex items-center justify-center gap-2 disabled:opacity-50"
                    >
                        {processing ? <LoaderCircle size={16} className="animate-spin" /> : <>Send Tip Now <Send size={15} /></>}
                    </button>
                </form>
            </section>
        </div>
    );
}
