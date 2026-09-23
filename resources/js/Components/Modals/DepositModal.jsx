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
    { basePrice: 10, coins: 5.0, bonusCoins: 0.5, name: 'Neon Spark' },
    { basePrice: 20, coins: 10.0, bonusCoins: 1.5, name: 'Golden Energy' },
    { basePrice: 50, coins: 25.0, bonusCoins: 5.0, name: 'Extra Bright', popular: true },
    { basePrice: 100, coins: 50.0, bonusCoins: 15.0, name: 'High Roller' },
    { basePrice: 250, coins: 125.0, bonusCoins: 40.0, name: 'The Full Glow' },
    { basePrice: 500, coins: 250.0, bonusCoins: 100.0, name: 'Cyber Whale' },
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
    const totalCoins = Number((selectedPack.coins + selectedPack.bonusCoins).toFixed(2));

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
            setFeedback({ type: 'error', message: 'Log in to claim or add SC to your wallet.' });
            return;
        }
        requestInFlight.current = true;
        setProcessing(type);
        setFeedback(null);
        const creditAmount = type === 'daily' ? 0.02 : totalCoins;
        const convertedPrice = Math.round(selectedPack.basePrice * curr.rate);
        try {
            const response = await axios.post('/deposit', {
                amount: creditAmount,
                method: type === 'daily' ? 'daily_sc_bonus' : 'card',
                pack_name: type === 'daily' ? 'Daily Free 0.02 SC' : selectedPack.name,
                price: type === 'daily' ? 0 : convertedPrice,
                currency: selectedCurrency,
            });
            if (response.data?.status !== 'success') {
                throw new Error('The credit was not confirmed. Please try again.');
            }
            setCreditedBalance(response.data.new_balance);
            playCoinSound(user?.sound_enabled !== false);
            setFeedback({ 
                type: 'success', 
                message: type === 'daily' 
                    ? 'Claimed 0.02 Free Daily SC! Balance updated.' 
                    : `${creditAmount.toFixed(2)} SC added to your wallet (Rate: 1 EUR = 0.5 SC). Receipt sent to your email.` 
            });
            router.reload({ only: ['auth'] });
        } catch (error) {
            setFeedback({ type: 'error', message: error.response?.data?.message || error.message || 'Unable to add SC. Please try again.' });
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
                        <h2>More SC.<br /><em>More good times.</em></h2>
                        <p>Pick your pack.<br />1 EUR = 0.5 SC value.</p>
                        <div className="nw-topup-balance">
                            <span>Your SC balance</span>
                            <strong>
                                <Coins size={20} />{Number(creditedBalance ?? user?.game_balance ?? 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}<small> SC</small>
                            </strong>
                        </div>
                    </div>
                    <img src="/images/landing/neon-lucky-cat.png" alt="" className="nw-topup-art" />
                    <span className="nw-topup-story-foot"><span>✦</span> PLAY IN YOUR ELEMENT</span>
                </aside>
                <div className="nw-topup-main">
                    <header className="nw-topup-heading">
                        <div className="flex items-center justify-between flex-wrap gap-2">
                            <span className="nw-topup-kicker"><Wallet size={13} /> YOUR PLAY WALLET <span><FlaskConical size={11} /> 1 EUR = 0.5 SC</span></span>
                            
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
                        <h2 id="topup-title">Top up your Sweeps Coins.</h2>
                        <p id="topup-description">Sweeps Coin packs starting from {getPriceInCurrency(10)} (1 EUR = 0.5 SC).</p>
                    </header>

                    <div className="nw-pack-heading">
                        <h3>Choose your pack ({selectedCurrency})</h3>
                        <span>Bonus SC included <Sparkles size={12} /></span>
                    </div>

                    <div className="nw-pack-grid" role="group" aria-label="Coin packages">
                        {coinPacks.map((pack, index) => (
                            <button 
                                key={pack.basePrice} 
                                aria-pressed={selectedPrice === pack.basePrice} 
                                aria-label={`${(pack.coins + pack.bonusCoins).toFixed(2)} SC including ${(pack.bonusCoins).toFixed(2)} bonus, ${getPriceInCurrency(pack.basePrice)}`} 
                                disabled={!!processing} 
                                onClick={() => { setSelectedPrice(pack.basePrice); setFeedback(null); }} 
                                className={`nw-coin-pack ${selectedPrice === pack.basePrice ? 'is-selected' : ''} ${pack.popular ? 'is-popular' : ''}`}
                            >
                                {pack.popular && <span className="nw-pack-popular">THE SWEET SPOT</span>}
                                <span className="nw-pack-check">{selectedPrice === pack.basePrice && <Check size={11} strokeWidth={3} />}</span>
                                <CoinArt tier={index} />
                                <span className="nw-pack-name">{pack.name}</span>
                                <strong>{(pack.coins + pack.bonusCoins).toFixed(1)}<small> SC</small></strong>
                                <span className="nw-pack-bonus">{(pack.coins).toFixed(1)} <b>+ {(pack.bonusCoins).toFixed(1)} bonus</b></span>
                                <span className="nw-pack-price">{getPriceInCurrency(pack.basePrice)}<small> {selectedCurrency}</small></span>
                            </button>
                        ))}
                        
                        {/* Daily Free Claim */}
                        <div className="nw-daily-pack">
                            <span className="nw-daily-icon"><Gift size={29} strokeWidth={1.5} /></span>
                            <span className="nw-pack-name">Your daily free claim</span>
                            <strong>0.02<small> SC</small></strong>
                            <span className="nw-daily-caption">
                                {!canClaimDaily && countdownStr 
                                    ? `Next claim in: ${countdownStr}` 
                                    : 'Free 0.02 SC every 24 hours.'}
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
                                    <>Claim Free 0.02 SC <ArrowRight size={13} /></>
                                )}
                            </button>
                        </div>
                    </div>

                    <div className="nw-topup-summary">
                        <div>
                            <span>You’ll receive</span>
                            <strong>{totalCoins.toFixed(2)} SC <small>includes {selectedPack.bonusCoins.toFixed(2)} bonus</small></strong>
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
                                <><LoaderCircle size={16} className="animate-spin" /> Adding SC…</>
                            ) : (
                                <>Add {totalCoins.toFixed(2)} SC ({getPriceInCurrency(selectedPack.basePrice)}) <ArrowRight size={17} /></>
                            )}
                        </button>
                        <p><FlaskConical size={12} /> Sweeps Coins Wallet • 1 EUR = 0.5 SC</p>
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

