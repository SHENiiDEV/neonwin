import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    Trophy, 
    Coins, 
    Gift, 
    Mail, 
    Sparkles, 
    ShieldCheck, 
    CheckCircle2, 
    FileText, 
    Building2,
    Calendar,
    ArrowRight
} from 'lucide-react';

export default function SweepsRules() {
    const { company } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyNumber = company?.number || 'HE 448921';
    const companyAddress = company?.address || 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus';
    const companyEmail = company?.email || 'support@neonwin.com';

    return (
        <MainLayout>
            <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                <Head title="Official Sweeps Rules — Neonwin Social Casino" />

                {/* Sub-nav */}
                <LegalNav currentPath="/sweeps-rules" />

                {/* Header Banner */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                    <div className="max-w-2xl relative z-10 text-center md:text-left">
                        <span className="nw-eyebrow mb-2 text-yellow-400">
                            <Trophy size={13} /> OFFICIAL PROMOTIONAL SWEEPSTAKES RULES
                        </span>
                        <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-2">
                            Sweeps Rules
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-300 font-medium">
                            NO PURCHASE NECESSARY TO ENTER OR WIN. A PURCHASE WILL NOT INCREASE YOUR CHANCES OF WINNING.
                        </p>
                    </div>

                    <div className="p-4 rounded-2xl bg-[#1d1627] border border-[#3e2e4e] text-xs text-gray-400 space-y-1">
                        <div className="text-white font-bold flex items-center gap-1.5">
                            <Coins size={14} className="text-yellow-400" />
                            <span>Currency Model</span>
                        </div>
                        <p className="font-mono text-[11px] text-yellow-300">Sweeps Coins (SC)</p>
                    </div>
                </div>

                {/* Content Box */}
                <div className="p-6 sm:p-10 rounded-3xl bg-[#14101e] border border-[#342742] space-y-8 text-xs text-gray-300 leading-relaxed shadow-xl">
                    
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Building2 size={16} className="text-yellow-400" />
                            1. Sponsor & Promotion Mechanics
                        </h2>
                        <p>
                            The Neonwin promotional sweepstakes are sponsored and administered by <strong className="text-white">{companyName}</strong>, Registration No. <strong className="text-white">{companyNumber}</strong>, located at <strong className="text-white">{companyAddress}</strong> ("Sponsor").
                        </p>
                        <p>
                            The sweepstakes operate continuously across the platform with random prize outcomes generated during game rounds on all participating slot and table titles.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Gift size={16} className="text-purple-400" />
                            2. Methods of Obtaining Free Sweeps Coins
                        </h2>
                        <p>
                            Eligible participants may obtain Sweeps Coins through any of the following free methods:
                        </p>
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <div className="p-4 rounded-2xl bg-[#1a1425] border border-[#3c2c4e] space-y-2">
                                <span className="font-bold text-white block">A. Daily Login Claim</span>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    Log in to your Neonwin account every 24 hours and click the Daily Gift button in the Top-Up dialog to instantly receive <strong className="text-yellow-400">50 Free SC</strong>.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-[#1a1425] border border-[#3c2c4e] space-y-2">
                                <span className="font-bold text-white block">B. Complimentary Packs</span>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    When purchasing social coin packs, players receive bonus Sweeps Coins automatically as a free complementary promotional gift.
                                </p>
                            </div>

                            <div className="p-4 rounded-2xl bg-[#1a1425] border border-[#3c2c4e] space-y-2">
                                <span className="font-bold text-white block">C. VIP XP Progression</span>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    Earn instant cashback (up to 18%), weekly rakeback (up to 20%), and milestone rank-up bonus coins (up to 5,000 SC) through regular gameplay.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <ShieldCheck size={16} className="text-cyan-400" />
                            3. Eligibility Requirements (18+)
                        </h2>
                        <p>
                            The sweepstakes are open solely to legal residents of jurisdictions where promotional social casinos are lawful, who are at least 18 years of age at the time of entry. Employees, officers, and contractors of the Sponsor and its aggregator affiliates are not eligible.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Coins size={16} className="text-emerald-400" />
                            4. Prize Outcomes & Redemptions
                        </h2>
                        <p>
                            Game outcomes are determined by certified RNG algorithms. Sweeps Coins won through gameplay may be eligible for prize redemption pursuant to account verification (KYC), adherence to 1x gameplay requirements, and compliance with minimum withdrawal thresholds.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Mail size={16} className="text-pink-400" />
                            5. Questions & Official Requests
                        </h2>
                        <p>
                            For inquiries regarding official Sweeps Rules or mail-in submission inquiries, contact <a href={`mailto:${companyEmail}`} className="text-yellow-400 font-bold hover:underline font-mono">{companyEmail}</a>.
                        </p>
                    </div>

                </div>
            </div>
        </MainLayout>
    );
}
