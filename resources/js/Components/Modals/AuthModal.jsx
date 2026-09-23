import React, { useState, useEffect } from 'react';
import { useForm, router, Link } from '@inertiajs/react';
import { 
    X, 
    Lock, 
    Mail, 
    User, 
    Sparkles, 
    Zap, 
    ArrowRight, 
    ShieldCheck, 
    Eye, 
    EyeOff, 
    Coins, 
    Gift, 
    Phone,
    Calendar,
    MapPin,
    Building,
    Globe,
    FileText,
    Loader2,
    Check
} from 'lucide-react';
import { WORLD_COUNTRIES } from '../../constants/countries';

export default function AuthModal({ isOpen, onClose, initialMode = 'login' }) {
    const [mode, setMode] = useState(initialMode); // 'login' | 'register'
    const [showPassword, setShowPassword] = useState(false);

    useEffect(() => {
        setMode(initialMode);
    }, [initialMode, isOpen]);

    const loginForm = useForm({
        email: '',
        password: '',
    });

    const registerForm = useForm({
        name: '',
        surname: '',
        email: '',
        phone: '',
        date_of_birth: '',
        street: '',
        city: '',
        country: 'Cyprus',
        postcode: '',
        password: '',
        terms: false,
    });

    if (!isOpen) return null;

    const handleLogin = (e) => {
        e.preventDefault();
        loginForm.post('/login', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    const handleRegister = (e) => {
        e.preventDefault();
        registerForm.post('/register', {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <div 
            className="nw-topup-backdrop" 
            onClick={e => { if (e.target === e.currentTarget) onClose(); }}
        >
            <section 
                role="dialog" 
                aria-modal="true" 
                aria-labelledby="auth-modal-title" 
                aria-describedby="auth-modal-description" 
                className="nw-topup"
                style={{ width: mode === 'register' ? '980px' : '880px' }}
            >
                {/* Close Button */}
                <button 
                    onClick={onClose} 
                    aria-label="Close authentication dialog" 
                    className="nw-topup-close"
                >
                    <X size={18} />
                </button>

                {/* Left Story Sidebar (Amethyst & Gold signature style) */}
                <aside className="nw-topup-story">
                    <div className="nw-topup-story-copy">
                        <span className="nw-eyebrow">
                            <Sparkles size={13} className="text-yellow-400" /> 
                            {mode === 'register' ? 'CREATE PLAYER ACCOUNT' : 'WELCOME BACK'}
                        </span>

                        {mode === 'register' ? (
                            <>
                                <h2>Your world of play.<br /><em>Join Neonwin today.</em></h2>
                                <p>Instant verified registration.<br />Explore 1,500+ slots, daily 100,000 Coins login gifts, and VIP cashback.</p>
                            </>
                        ) : (
                            <>
                                <h2>Good times await.<br /><em>Jump into the action.</em></h2>
                                <p>Access your wallet balance,<br />favorite slot games and VIP progression in one click.</p>
                            </>
                        )}

                        {/* Member Perks Box */}
                        <div className="nw-topup-balance space-y-2.5">
                            <span className="text-[10px] text-[#b6a3c5] uppercase font-bold tracking-wider block">
                                Verified Member Benefits
                            </span>
                            
                            <div className="flex items-center gap-2 text-xs text-white">
                                <Coins size={15} className="text-yellow-400 flex-shrink-0" />
                                <span><strong>100,000 Coins Free</strong> Daily Login Gift</span>
                            </div>

                            <div className="flex items-center gap-2 text-xs text-white">
                                <ShieldCheck size={15} className="text-emerald-400 flex-shrink-0" />
                                <span><strong>0x Wagering</strong> on All Rewards</span>
                            </div>

                            <div className="flex items-center gap-2 text-xs text-white">
                                <Zap size={15} className="text-cyan-400 flex-shrink-0" />
                                <span><strong>Fast Redemptions</strong> with Full Verification</span>
                            </div>
                        </div>
                    </div>

                    {/* Mascot Illustration */}
                    <img 
                        src="/images/landing/neon-lucky-cat.png" 
                        alt="Neonwin Lucky Mascot" 
                        className="nw-topup-art" 
                    />

                    <span className="nw-topup-story-foot">
                        <span>✦</span> 100% PROVABLY FAIR SOCIAL GAMING
                    </span>
                </aside>

                {/* Right Main Panel */}
                <div className="nw-topup-main">
                    <header className="nw-topup-heading">
                        <span className="nw-topup-kicker">
                            <Zap size={13} /> INSTANT & SECURE ACCESS 
                            <span><ShieldCheck size={11} /> 256-bit SSL</span>
                        </span>
                        <h2 id="auth-modal-title">
                            {mode === 'register' ? 'Register your player profile.' : 'Sign in to your wallet.'}
                        </h2>
                        <p id="auth-modal-description">
                            {mode === 'register' 
                                ? 'Complete your verified details below to enable seamless payments & redemptions.' 
                                : 'Enter your email and password to access your player account and games.'}
                        </p>
                    </header>

                    {/* Mode Tabs */}
                    <div className="nw-topup-tabs" role="group" aria-label="Authentication mode">
                        <button 
                            aria-pressed={mode === 'login'} 
                            onClick={() => setMode('login')} 
                            className={mode === 'login' ? 'is-active' : ''}
                        >
                            <User size={16} /> Sign In
                        </button>
                        <button 
                            aria-pressed={mode === 'register'} 
                            onClick={() => setMode('register')} 
                            className={mode === 'register' ? 'is-active' : ''}
                        >
                            <Sparkles size={16} /> Register Profile
                        </button>
                    </div>

                    {/* LOGIN FORM */}
                    {mode === 'login' ? (
                        <form onSubmit={handleLogin} className="mt-5 space-y-4">
                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-gray-300 block">Email Address</label>
                                <div className="relative">
                                    <Mail className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input 
                                        type="email"
                                        required
                                        value={loginForm.data.email}
                                        onChange={e => loginForm.setData('email', e.target.value)}
                                        placeholder="player@example.com"
                                        className="w-full pl-10 pr-4 py-2.5 rounded-xl bg-[#1c1626] border border-[#3f2f4e] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400 transition"
                                    />
                                </div>
                                {loginForm.errors.email && (
                                    <p className="text-[10px] text-rose-400">{loginForm.errors.email}</p>
                                )}
                            </div>

                            <div className="space-y-1.5">
                                <label className="text-xs font-semibold text-gray-300 block">Password</label>
                                <div className="relative">
                                    <Lock className="w-4 h-4 text-gray-500 absolute left-3.5 top-1/2 -translate-y-1/2" />
                                    <input 
                                        type={showPassword ? 'text' : 'password'}
                                        required
                                        value={loginForm.data.password}
                                        onChange={e => loginForm.setData('password', e.target.value)}
                                        placeholder="••••••••"
                                        className="w-full pl-10 pr-10 py-2.5 rounded-xl bg-[#1c1626] border border-[#3f2f4e] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400 transition"
                                    />
                                    <button 
                                        type="button" 
                                        onClick={() => setShowPassword(!showPassword)}
                                        className="absolute right-3.5 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300"
                                        tabIndex={-1}
                                    >
                                        {showPassword ? <EyeOff size={15} /> : <Eye size={15} />}
                                    </button>
                                </div>
                                {loginForm.errors.password && (
                                    <p className="text-[10px] text-rose-400">{loginForm.errors.password}</p>
                                )}
                            </div>

                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={loginForm.processing}
                                    className="w-full nw-button nw-button-purple py-3 text-xs font-bold flex items-center justify-between"
                                >
                                    {loginForm.processing ? (
                                        <>
                                            <span className="flex items-center gap-2">
                                                <Loader2 size={16} className="animate-spin" /> Signing in...
                                            </span>
                                            <span />
                                        </>
                                    ) : (
                                        <>
                                            <span>Sign In to Play</span>
                                            <ArrowRight size={16} />
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    ) : (
                        /* EXPANDED VERIFIED REGISTER FORM */
                        <form onSubmit={handleRegister} className="mt-4 space-y-4">
                            <div className="max-h-[400px] overflow-y-auto no-scrollbar space-y-4 pr-1">
                                
                                {/* 1. Account Credentials */}
                                <div className="space-y-3 p-3.5 rounded-2xl bg-[#181322] border border-[#352542]">
                                    <span className="text-[10px] font-bold text-yellow-400 uppercase tracking-wider block">
                                        1. Account Credentials
                                    </span>
                                    
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">Email Address *</label>
                                            <div className="relative">
                                                <Mail className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="email"
                                                    required
                                                    value={registerForm.data.email}
                                                    onChange={e => registerForm.setData('email', e.target.value)}
                                                    placeholder="player@example.com"
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.email && <p className="text-[10px] text-rose-400">{registerForm.errors.email}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">Password (min. 6) *</label>
                                            <div className="relative">
                                                <Lock className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type={showPassword ? 'text' : 'password'}
                                                    required
                                                    minLength={6}
                                                    value={registerForm.data.password}
                                                    onChange={e => registerForm.setData('password', e.target.value)}
                                                    placeholder="••••••••"
                                                    className="w-full pl-9 pr-9 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                                <button 
                                                    type="button" 
                                                    onClick={() => setShowPassword(!showPassword)}
                                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-300"
                                                    tabIndex={-1}
                                                >
                                                    {showPassword ? <EyeOff size={14} /> : <Eye size={14} />}
                                                </button>
                                            </div>
                                            {registerForm.errors.password && <p className="text-[10px] text-rose-400">{registerForm.errors.password}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* 2. Personal Information */}
                                <div className="space-y-3 p-3.5 rounded-2xl bg-[#181322] border border-[#352542]">
                                    <span className="text-[10px] font-bold text-yellow-400 uppercase tracking-wider block">
                                        2. Personal Information
                                    </span>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">First Name *</label>
                                            <div className="relative">
                                                <User className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="text"
                                                    required
                                                    value={registerForm.data.name}
                                                    onChange={e => registerForm.setData('name', e.target.value)}
                                                    placeholder="John"
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.name && <p className="text-[10px] text-rose-400">{registerForm.errors.name}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">Surname (Last Name) *</label>
                                            <div className="relative">
                                                <User className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="text"
                                                    required
                                                    value={registerForm.data.surname}
                                                    onChange={e => registerForm.setData('surname', e.target.value)}
                                                    placeholder="Doe"
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.surname && <p className="text-[10px] text-rose-400">{registerForm.errors.surname}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">Phone Number *</label>
                                            <div className="relative">
                                                <Phone className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="tel"
                                                    required
                                                    value={registerForm.data.phone}
                                                    onChange={e => registerForm.setData('phone', e.target.value)}
                                                    placeholder="+357 99 123456"
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.phone && <p className="text-[10px] text-rose-400">{registerForm.errors.phone}</p>}
                                        </div>

                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">Date of Birth (18+) *</label>
                                            <div className="relative">
                                                <Calendar className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="date"
                                                    required
                                                    value={registerForm.data.date_of_birth}
                                                    onChange={e => registerForm.setData('date_of_birth', e.target.value)}
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.date_of_birth && <p className="text-[10px] text-rose-400">{registerForm.errors.date_of_birth}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* 3. Residential Address (4 Sections) */}
                                <div className="space-y-3 p-3.5 rounded-2xl bg-[#181322] border border-[#352542]">
                                    <span className="text-[10px] font-bold text-yellow-400 uppercase tracking-wider block">
                                        3. Residential Address
                                    </span>

                                    {/* 1. Street, house number, apartment */}
                                    <div className="space-y-1">
                                        <label className="text-[11px] font-semibold text-gray-300 block">1. Street, House & Apt Number *</label>
                                        <div className="relative">
                                            <MapPin className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                            <input 
                                                type="text"
                                                required
                                                value={registerForm.data.street}
                                                onChange={e => registerForm.setData('street', e.target.value)}
                                                placeholder="Agiou Pavlou 15, Apt 302"
                                                className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                            />
                                        </div>
                                        {registerForm.errors.street && <p className="text-[10px] text-rose-400">{registerForm.errors.street}</p>}
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        {/* 2. City */}
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">2. City *</label>
                                            <div className="relative">
                                                <Building className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <input 
                                                    type="text"
                                                    required
                                                    value={registerForm.data.city}
                                                    onChange={e => registerForm.setData('city', e.target.value)}
                                                    placeholder="Nicosia"
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                                />
                                            </div>
                                            {registerForm.errors.city && <p className="text-[10px] text-rose-400">{registerForm.errors.city}</p>}
                                        </div>

                                        {/* 3. Country Dropdown */}
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">3. Country *</label>
                                            <div className="relative">
                                                <Globe className="w-3.5 h-3.5 text-gray-500 absolute left-3 top-1/2 -translate-y-1/2" />
                                                <select 
                                                    required
                                                    value={registerForm.data.country}
                                                    onChange={e => registerForm.setData('country', e.target.value)}
                                                    className="w-full pl-9 pr-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white text-xs focus:outline-none focus:border-purple-400"
                                                >
                                                    {WORLD_COUNTRIES.map(country => (
                                                        <option key={country} value={country}>
                                                            {country}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            {registerForm.errors.country && <p className="text-[10px] text-rose-400">{registerForm.errors.country}</p>}
                                        </div>

                                        {/* 4. Post Code */}
                                        <div className="space-y-1">
                                            <label className="text-[11px] font-semibold text-gray-300 block">4. Post Code / ZIP *</label>
                                            <input 
                                                type="text"
                                                required
                                                value={registerForm.data.postcode}
                                                onChange={e => registerForm.setData('postcode', e.target.value)}
                                                placeholder="1105"
                                                className="w-full px-3 py-2 rounded-xl bg-[#14101e] border border-[#3b2d49] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                            />
                                            {registerForm.errors.postcode && <p className="text-[10px] text-rose-400">{registerForm.errors.postcode}</p>}
                                        </div>
                                    </div>
                                </div>

                                {/* Terms & Conditions Checkbox */}
                                <div className="p-3 rounded-2xl bg-[#14101e] border border-[#332545]">
                                    <label className="flex items-start gap-2.5 text-xs text-gray-300 cursor-pointer select-none">
                                        <input 
                                            type="checkbox"
                                            required
                                            checked={registerForm.data.terms}
                                            onChange={e => registerForm.setData('terms', e.target.checked)}
                                            className="mt-0.5 rounded border-purple-500 text-purple-600 focus:ring-0"
                                        />
                                        <span className="leading-snug text-[11px]">
                                            I agree to the <Link href="/terms" onClick={onClose} className="text-yellow-400 underline font-semibold hover:text-yellow-300">Terms & Conditions</Link> and <Link href="/privacy" onClick={onClose} className="text-yellow-400 underline font-semibold hover:text-yellow-300">Privacy Policy</Link>.
                                        </span>
                                    </label>
                                    {registerForm.errors.terms && (
                                        <p className="text-[10px] text-rose-400 mt-1 pl-6">{registerForm.errors.terms}</p>
                                    )}
                                </div>

                            </div>

                            {/* Submit Button */}
                            <div className="pt-2">
                                <button
                                    type="submit"
                                    disabled={registerForm.processing}
                                    className="w-full nw-button nw-button-purple py-3 text-xs font-bold flex items-center justify-between shadow-[0_0_25px_rgba(168,85,247,0.3)]"
                                >
                                    {registerForm.processing ? (
                                        <>
                                            <span className="flex items-center gap-2">
                                                <Loader2 size={16} className="animate-spin" /> Verifying and creating profile...
                                            </span>
                                            <span />
                                        </>
                                    ) : (
                                        <>
                                            <span>Complete Registration & Play</span>
                                            <ArrowRight size={16} />
                                        </>
                                    )}
                                </button>
                            </div>
                        </form>
                    )}

                    <footer className="nw-topup-foot">
                        <span>18+ · Social Casino Play</span>
                        <div className="flex items-center gap-3">
                            <Link href="/privacy" onClick={onClose} className="hover:text-purple-300">Privacy</Link>
                            <span>•</span>
                            <Link href="/terms" onClick={onClose} className="hover:text-purple-300">Terms</Link>
                            <span>•</span>
                            <Link href="/sweeps-rules" onClick={onClose} className="hover:text-purple-300">Sweeps</Link>
                        </div>
                    </footer>
                </div>
            </section>
        </div>
    );
}
