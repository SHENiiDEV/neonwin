import React, { useEffect, useRef, useState } from 'react';
import { usePage, router, Link } from '@inertiajs/react';
import { X, Check, ArrowRight, Coins, Sparkles, Wallet, Gift, ChevronRight, LoaderCircle, CircleAlert, FlaskConical, Globe, CreditCard } from 'lucide-react';
import axios from 'axios';
import { playCoinSound } from '../../utils/soundEffects';

const currencies = [
    { code: 'EUR', symbol: '€', rate: 1.0, label: 'EUR (€)' },
    { code: 'USD', symbol: '$', rate: 1.08, label: 'USD ($)' },
    { code: 'GBP', symbol: '£', rate: 0.85, label: 'GBP (£)' },
];

const coinPacks = [
    { basePrice: 50, coins: 2500000, bonusCoins: 300000, name: 'Golden Energy', popular: true },
    { basePrice: 100, coins: 5000000, bonusCoins: 800000, name: 'Extra Bright' },
    { basePrice: 250, coins: 12500000, bonusCoins: 2500000, name: 'High Roller' },
    { basePrice: 500, coins: 25000000, bonusCoins: 6000000, name: 'The Full Glow' },
    { basePrice: 1000, coins: 50000000, bonusCoins: 15000000, name: 'Neon Emperor' },
    { basePrice: 2500, coins: 125000000, bonusCoins: 45000000, name: 'Cyber Whale' },
];

function CoinArt({ tier = 0 }) {
    return <span className={`nw-coin-art nw-coin-tier-${tier % 5}`} aria-hidden="true"><span>✦</span><span>✦</span><span>✦</span></span>;
}

export default function DepositModal({ isOpen, onClose }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const [selectedCurrency, setSelectedCurrency] = useState('EUR');
    const [selectedPrice, setSelectedPrice] = useState(50);
    const [processing, setProcessing] = useState(null);
    const [feedback, setFeedback] = useState(null);
    const [creditedBalance, setCreditedBalance] = useState(null);
    const [countdownStr, setCountdownStr] = useState('');
    const requestInFlight = useRef(false);
    
    const canClaimDaily = user ? (user.can_claim_daily_bonus !== false) : true;

    useEffect(() => {
        if (!user?.next_daily_bonus_at || canClaimDaily) {
            setCountdownStr('');
            return;
        }

        const updateTimer = () => {
            const nextTime = new Date(user.next_daily_bonus_at).getTime();
            const now = Date.now();
            const diff = nextTime - now;
            if (diff <= 0) {
                setCountdownStr('');
                return;
            }
            const hours = Math.floor(diff / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            setCountdownStr(`${hours}h ${minutes}m ${seconds}s`);
        };

        updateTimer();
        const interval = setInterval(updateTimer, 1000);
        return () => clearInterval(interval);
    }, [user?.next_daily_bonus_at, canClaimDaily]);
    
    const curr = currencies.find(c => c.code === selectedCurrency) || currencies[0];
    const selectedPack = coinPacks.find(pack => pack.basePrice === selectedPrice) || coinPacks[0];
    const totalCoins = selectedPack.coins + selectedPack.bonusCoins;

    const getPriceInCurrency = (baseEur) => {
        const converted = Math.round(baseEur * curr.rate);
        return `${curr.symbol}${converted.toLocaleString('en-US')}`;
    };

    useEffect(() => {
        if (isOpen) setFeedback(null);
    }, [isOpen]);

    if (!isOpen) return null;

    const creditCoins = async (type) => {
        if (requestInFlight.current) return;
        if (!user) {
            setFeedback({ type: 'error', message: 'Log in to claim or add coins to your wallet.' });
            return;
        }
        requestInFlight.current = true;
        setProcessing(type);
        setFeedback(null);
        const creditAmount = type === 'daily' ? 100000 : totalCoins;
        try {
            const response = await axios.post('/deposit', {
                amount: creditAmount,
                method: type === 'daily' ? 'daily_sc_bonus' : 'card',
            });
            if (response.data?.status !== 'success') {
                throw new Error('The credit was not confirmed. Please try again.');
            }
            setCreditedBalance(response.data.new_balance);
            playCoinSound(user?.sound_enabled !== false);
            setFeedback({ 
                type: 'success', 
                message: type === 'daily' 
                    ? 'Claimed 100,000 Free Daily Coins! Balance updated.' 
                    : `${creditAmount.toLocaleString('en-US')} Coins added to your wallet. You’re ready to play.` 
            });
            router.reload({ only: ['auth'] });
        } catch (error) {
            setFeedback({ type: 'error', message: error.response?.data?.message || error.message || 'Unable to add coins. Please try again.' });
        } finally {
            requestInFlight.current = false;
            setProcessing(null);
        }
    };

    return (
        <div className="nw-topup-backdrop" onClick={event => { if (event.target === event.currentTarget && !processing) onClose(); }}>
            <section role="dialog" aria-modal="true" aria-labelledby="topup-title" aria-describedby="topup-description" className="nw-topup">
                <button onClick={onClose} aria-label="Close Get Sweeps Coins dialog" className="nw-topup-close"><X size={18} /></button>
                <aside className="nw-topup-story">
                    <div className="nw-topup-story-copy">
                        <span className="nw-eyebrow"><Sparkles size={13} /> A LITTLE NEON MAGIC</span>
                        <h2>More coins.<br /><em>More good times.</em></h2>
                        <p>Pick your pack.<br />Find your next favorite.</p>
                        <div className="nw-topup-balance">
                            <span>Your Coin balance</span>
                            <strong>
                                <Coins size={20} />{Number(creditedBalance ?? user?.game_balance ?? 0).toLocaleString('en-US')}<small> Coins</small>
                            </strong>
                        </div>
                    </div>
                    <img src="/images/landing/neon-lucky-cat.png" alt="" className="nw-topup-art" />
                    <span className="nw-topup-story-foot"><span>✦</span> PLAY IN YOUR ELEMENT</span>
                </aside>
                <div className="nw-topup-main">
                    <header className="nw-topup-heading">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <span className="nw-topup-kicker"><Wallet size={13} /> YOUR PLAY WALLET <span><FlaskConical size={11} /> Test mode</span></span>
                            
                            {/* Currency Selector: EUR / GBP / USD */}
                            <div className="flex items-center gap-1 bg-[#160f22] p-1 rounded-xl border border-[#3b284c]">
                                <Globe size={12} className="text-gray-400 ml-1 mr-0.5" />
                                {currencies.map(c => (
                                    <button
                                        key={c.code}
                                        type="button"
                                        onClick={() => setSelectedCurrency(c.code)}
                                        className={`px-2.5 py-1 rounded-lg text-[10px] font-bold transition ${
                                            selectedCurrency === c.code 
                                                ? 'bg-purple-600 text-white shadow-sm' 
                                                : 'text-gray-400 hover:text-white'
                                        }`}
                                    >
                                        {c.code} ({c.symbol})
                                    </button>
                                ))}
                            </div>
                        </div>
                        <h2 id="topup-title">Top up your good times.</h2>
                        <p id="topup-description">High value coin packs starting from {getPriceInCurrency(50)}.</p>
                    </header>

                    <div className="nw-pack-heading">
                        <h3>Choose your pack ({selectedCurrency})</h3>
                        <span>Bonus Coins included <Sparkles size={12} /></span>
                    </div>

                    <div className="nw-pack-grid" role="group" aria-label="Coin packages">
                        {coinPacks.map((pack, index) => (
                            <button 
                                key={pack.basePrice} 
                                aria-pressed={selectedPrice === pack.basePrice} 
                                aria-label={`${(pack.coins + pack.bonusCoins).toLocaleString()} Coins including ${(pack.bonusCoins).toLocaleString()} bonus, ${getPriceInCurrency(pack.basePrice)}`} 
                                disabled={!!processing} 
                                onClick={() => { setSelectedPrice(pack.basePrice); setFeedback(null); }} 
                                className={`nw-coin-pack ${selectedPrice === pack.basePrice ? 'is-selected' : ''} ${pack.popular ? 'is-popular' : ''}`}
                            >
                                {pack.popular && <span className="nw-pack-popular">THE SWEET SPOT</span>}
                                <span className="nw-pack-check">{selectedPrice === pack.basePrice && <Check size={11} strokeWidth={3} />}</span>
                                <CoinArt tier={index} />
                                <span className="nw-pack-name">{pack.name}</span>
                                <strong>{((pack.coins + pack.bonusCoins) / 1000000).toFixed(1)}M<small> Coins</small></strong>
                                <span className="nw-pack-bonus">{(pack.coins).toLocaleString('en-US')} <b>+ {(pack.bonusCoins).toLocaleString('en-US')} bonus</b></span>
                                <span className="nw-pack-price">{getPriceInCurrency(pack.basePrice)}<small> {selectedCurrency}</small></span>
                            </button>
                        ))}
                        
                        {/* Daily Free Claim */}
                        <div className="nw-daily-pack">
                            <span className="nw-daily-icon"><Gift size={29} strokeWidth={1.5} /></span>
                            <span className="nw-pack-name">Your daily free claim</span>
                            <strong>100,000<small> Coins</small></strong>
                            <span className="nw-daily-caption">
                                {!canClaimDaily && countdownStr 
                                    ? `Next claim in: ${countdownStr}` 
                                    : 'Free 100,000 Coins every 24 hours.'}
                            </span>
                            <button 
                                disabled={!!processing || !canClaimDaily} 
                                onClick={() => creditCoins('daily')}
                                className={!canClaimDaily ? 'opacity-60 cursor-not-allowed !bg-[#221830] !text-gray-400' : ''}
                            >
                                {processing === 'daily' ? (
                                    <LoaderCircle size={13} className="animate-spin" />
                                ) : !canClaimDaily ? (
                                    <>Claimed ({countdownStr || 'Locked'}) <Check size={13} /></>
                                ) : (
                                    <>Claim Free 100K <ArrowRight size={13} /></>
                                )}
                            </button>
                        </div>
                    </div>

                    <div className="nw-topup-summary">
                        <div>
                            <span>You’ll receive</span>
                            <strong>{totalCoins.toLocaleString('en-US')} Coins <small>includes {selectedPack.bonusCoins.toLocaleString('en-US')} bonus</small></strong>
                        </div>
                        <div>
                            <span>Pack price</span>
                            <strong>{getPriceInCurrency(selectedPack.basePrice)} <small>{selectedCurrency}</small></strong>
                        </div>
                    </div>

                    {feedback && (
                        <div className={`nw-topup-feedback ${feedback.type}`} role={feedback.type === 'error' ? 'alert' : 'status'}>
                            {feedback.type === 'success' ? <Check size={17} /> : <CircleAlert size={17} />}
                            <span>{feedback.message}</span>
                        </div>
                    )}

                    <div className="nw-topup-checkout">
                        <button 
                            className="nw-button nw-button-purple" 
                            disabled={!!processing} 
                            onClick={() => creditCoins('pack')}
                        >
                            {processing === 'pack' ? (
                                <><LoaderCircle size={16} className="animate-spin" /> Adding coins…</>
                            ) : (
                                <>Add {totalCoins.toLocaleString('en-US')} Coins ({getPriceInCurrency(selectedPack.basePrice)}) <ArrowRight size={17} /></>
                            )}
                        </button>
                        <p><FlaskConical size={12} /> Demo wallet test mode. No real charge incurred.</p>
                    </div>

                    <footer className="nw-topup-foot">
                        <span>18+ · Play responsibly</span>
                        <Link href="/sweeps-rules" onClick={onClose}>Sweeps rules <ChevronRight size={12} /></Link>
                    </footer>
                </div>
            </section>
        </div>
    );
}
