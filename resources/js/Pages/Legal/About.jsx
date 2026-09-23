import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    Sparkles, 
    ShieldCheck, 
    Trophy, 
    Users, 
    Zap, 
    Gamepad2, 
    Radio, 
    Coins, 
    Flame, 
    CheckCircle2, 
    ArrowRight,
    Lock,
    Building2,
    Mail
} from 'lucide-react';

export default function About() {
    const { company } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyNumber = company?.number || 'HE 448921';
    const companyAddress = company?.address || 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus';
    const companyEmail = company?.email || 'support@neonwin.com';

    return (
        <MainLayout>
            {({ openDeposit }) => (
                <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                    <Head title="About Us — Neonwin Social Casino" />

                    {/* Sub-nav */}
                    <LegalNav currentPath="/about" />

                    {/* Hero Banner */}
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-14 flex flex-col md:flex-row items-center justify-between gap-8 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                        <div className="max-w-2xl relative z-10 text-center md:text-left">
                            <span className="nw-eyebrow mb-2 text-yellow-400">
                                <Sparkles size={13} /> THE SOCIAL CASINO REVOLUTION
                            </span>
                            <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-4">
                                Next-Gen Play.<br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-purple-400 via-fuchsia-300 to-yellow-400">
                                    Golden Energy.
                                </span>
                            </h1>
                            <p className="text-xs sm:text-sm text-gray-300 leading-relaxed font-medium max-w-xl mb-6">
                                Neonwin combines high-octane cyber aesthetics with transparent, certified social casino entertainment. Over 1,500 games, instant real-time gameplay, and a vibrant player community.
                            </p>

                            <div className="flex flex-wrap items-center justify-center md:justify-start gap-4">
                                <Link 
                                    href="/" 
                                    className="nw-button nw-button-purple px-6 py-3 text-xs flex items-center gap-2 font-bold"
                                >
                                    <span>Play Official Slots Now</span>
                                    <ArrowRight size={14} />
                                </Link>
                                <Link 
                                    href="/vip" 
                                    className="px-5 py-2.5 rounded-xl bg-[#21182e] hover:bg-[#2c203d] border border-[#4d365e] text-yellow-300 text-xs font-bold transition flex items-center gap-2"
                                >
                                    <Trophy size={14} className="text-yellow-400" />
                                    <span>Explore VIP Perks</span>
                                </Link>
                            </div>
                        </div>

                        {/* Visual Mascot */}
                        <div className="relative w-56 h-56 sm:w-64 sm:h-64 flex items-center justify-center flex-shrink-0">
                            <img 
                                src="/images/landing/neon-lucky-cat.png" 
                                alt="Neonwin Mascot" 
                                className="w-full h-full object-contain filter drop-shadow-[0_15px_30px_rgba(168,85,247,0.3)]"
                            />
                        </div>
                    </div>

                    {/* Stats Metrics Grid */}
                    <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div className="p-5 rounded-2xl bg-[#151220] border border-[#342742] flex flex-col">
                            <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Game Portfolio</span>
                            <strong className="font-heading text-2xl sm:text-3xl text-white font-black mt-1">1,500+</strong>
                            <span className="text-[10px] text-purple-400 mt-1">Slots, Live & Crash</span>
                        </div>
                        <div className="p-5 rounded-2xl bg-[#151220] border border-[#342742] flex flex-col">
                            <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Licensed Providers</span>
                            <strong className="font-heading text-2xl sm:text-3xl text-yellow-400 font-black mt-1">26+</strong>
                            <span className="text-[10px] text-yellow-500 mt-1">Pragmatic, PG Soft, Hacksaw</span>
                        </div>
                        <div className="p-5 rounded-2xl bg-[#151220] border border-[#342742] flex flex-col">
                            <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Max VIP Cashback</span>
                            <strong className="font-heading text-2xl sm:text-3xl text-emerald-400 font-black mt-1">18%</strong>
                            <span className="text-[10px] text-emerald-500 mt-1">0x Wager Instant Credit</span>
                        </div>
                        <div className="p-5 rounded-2xl bg-[#151220] border border-[#342742] flex flex-col">
                            <span className="text-[10px] text-gray-400 uppercase tracking-wider font-bold">Support Availability</span>
                            <strong className="font-heading text-2xl sm:text-3xl text-cyan-400 font-black mt-1">24/7/365</strong>
                            <span className="text-[10px] text-cyan-500 mt-1">Real-time Live Chat</span>
                        </div>
                    </div>

                    {/* Core Pillars */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="p-6 sm:p-8 rounded-3xl bg-[#151220] border border-[#342742] space-y-3">
                            <div className="w-12 h-12 rounded-2xl bg-purple-900/40 border border-purple-500/40 flex items-center justify-center text-purple-300">
                                <Gamepad2 size={24} />
                            </div>
                            <h3 className="font-heading font-black text-lg text-white">Official Aggregated Games</h3>
                            <p className="text-xs text-gray-300 leading-relaxed">
                                Our entire game library is integrated directly via high-availability NexusGGR game aggregation servers, ensuring authentic math models, verified RTPs, and studio-grade graphic rendering.
                            </p>
                        </div>

                        <div className="p-6 sm:p-8 rounded-3xl bg-[#151220] border border-[#342742] space-y-3">
                            <div className="w-12 h-12 rounded-2xl bg-amber-900/40 border border-amber-500/40 flex items-center justify-center text-yellow-400">
                                <ShieldCheck size={24} />
                            </div>
                            <h3 className="font-heading font-black text-lg text-white">Provably Fair & Certified RNG</h3>
                            <p className="text-xs text-gray-300 leading-relaxed">
                                We hold ourselves to the highest standards of transparency. Every spin and deal is computed on independently audited Random Number Generators that cannot be modified by any party.
                            </p>
                        </div>

                        <div className="p-6 sm:p-8 rounded-3xl bg-[#151220] border border-[#342742] space-y-3">
                            <div className="w-12 h-12 rounded-2xl bg-cyan-900/40 border border-cyan-500/40 flex items-center justify-center text-cyan-300">
                                <Trophy size={24} />
                            </div>
                            <h3 className="font-heading font-black text-lg text-white">Social Play & Gamification</h3>
                            <p className="text-xs text-gray-300 leading-relaxed">
                                We believe playing should be thrilling and rewarding. Climb through 5 prestige VIP ranks, win in live community tournament races, and celebrate with peers in live community chat.
                            </p>
                        </div>
                    </div>

                    {/* Corporate & Regulatory Box */}
                    <div className="p-6 sm:p-8 rounded-3xl bg-[#14101e] border border-[#372746] space-y-4">
                        <div className="flex items-center gap-3 text-white font-heading font-bold text-base">
                            <Building2 size={20} className="text-yellow-400" />
                            <span>Corporate Entity & Regulatory Information</span>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 text-xs text-gray-300 leading-relaxed pt-1">
                            <div>
                                <p>
                                    Neonwin is owned and operated by <strong className="text-white">{companyName}</strong>, an international digital entertainment company incorporated under the laws of Cyprus with Registration Number <strong className="text-white">{companyNumber}</strong>.
                                </p>
                                <p className="mt-3">
                                    Registered Office Address:<br />
                                    <span className="text-gray-400">{companyAddress}</span>
                                </p>
                            </div>

                            <div className="space-y-3">
                                <p>
                                    For business cooperation, compliance verification, or general customer support, please contact our team:
                                </p>
                                <div className="flex items-center gap-2 text-yellow-400 font-mono font-bold">
                                    <Mail size={14} />
                                    <a href={`mailto:${companyEmail}`} className="hover:underline">{companyEmail}</a>
                                </div>
                                <div className="text-[11px] text-gray-500 flex items-center gap-2">
                                    <Lock size={12} className="text-emerald-400" />
                                    <span>256-bit SSL Data Encryption • PCI DSS Level 1 Certified</span>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            )}
        </MainLayout>
    );
}
