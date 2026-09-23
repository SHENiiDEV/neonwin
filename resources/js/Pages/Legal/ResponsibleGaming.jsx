import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    HeartHandshake, 
    ShieldCheck, 
    Sliders, 
    Clock, 
    UserX, 
    HelpCircle, 
    ExternalLink, 
    Building2,
    Mail,
    AlertTriangle
} from 'lucide-react';

export default function ResponsibleGaming() {
    const { company } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyEmail = company?.email || 'support@neonwin.com';

    return (
        <MainLayout>
            <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                <Head title="Responsible Social Gaming — Neonwin" />

                {/* Sub-nav */}
                <LegalNav currentPath="/responsible-gaming" />

                {/* Header Banner */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                    <div className="max-w-2xl relative z-10 text-center md:text-left">
                        <span className="nw-eyebrow mb-2 text-rose-400">
                            <HeartHandshake size={13} /> PLAYER WELFARE & CONTROL
                        </span>
                        <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-2">
                            Responsible Gaming
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-300 font-medium">
                            Play for fun. Stay in control. Tools and support to keep your gameplay safe.
                        </p>
                    </div>

                    <div className="p-4 rounded-2xl bg-[#1d1627] border border-[#3e2e4e] text-xs text-gray-400 space-y-1">
                        <div className="text-white font-bold flex items-center gap-1.5">
                            <ShieldCheck size={14} className="text-emerald-400" />
                            <span>Player Protection</span>
                        </div>
                        <p className="font-mono text-[11px] text-emerald-300">Self-Exclusion Available</p>
                    </div>
                </div>

                {/* Content Box */}
                <div className="p-6 sm:p-10 rounded-3xl bg-[#14101e] border border-[#342742] space-y-8 text-xs text-gray-300 leading-relaxed shadow-xl">
                    
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <ShieldCheck size={16} className="text-yellow-400" />
                            1. Our Commitment to Player Welfare
                        </h2>
                        <p>
                            At Neonwin, social casino gaming is designed exclusively for fun, excitement, and community entertainment. We are dedicated to providing a safe, transparent, and supportive environment equipped with tools to help you manage your gameplay responsibly.
                        </p>
                    </div>

                    <div className="space-y-4">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Sliders size={16} className="text-purple-400" />
                            2. Player Protection & Control Tools
                        </h2>
                        
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="p-5 rounded-2xl bg-[#191325] border border-[#3c2b4c] space-y-2">
                                <div className="w-8 h-8 rounded-lg bg-purple-900/40 text-purple-300 flex items-center justify-center">
                                    <Clock size={18} />
                                </div>
                                <strong className="text-white block font-bold">Session Reminders & Time Limits</strong>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    Set pop-up session notifications to keep track of the time you spend enjoying our games.
                                </p>
                            </div>

                            <div className="p-5 rounded-2xl bg-[#191325] border border-[#3c2b4c] space-y-2">
                                <div className="w-8 h-8 rounded-lg bg-amber-900/40 text-yellow-400 flex items-center justify-center">
                                    <AlertTriangle size={18} />
                                </div>
                                <strong className="text-white block font-bold">Daily / Weekly Coin Limits</strong>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    Restrict the amount of entertainment coin packages you can acquire within a given timeframe.
                                </p>
                            </div>

                            <div className="p-5 rounded-2xl bg-[#191325] border border-[#3c2b4c] space-y-2">
                                <div className="w-8 h-8 rounded-lg bg-rose-900/40 text-rose-400 flex items-center justify-center">
                                    <UserX size={18} />
                                </div>
                                <strong className="text-white block font-bold">Take a Break & Self-Exclusion</strong>
                                <p className="text-[11px] text-gray-400 leading-relaxed">
                                    Request a temporary cooling-off period (24 hours to 30 days) or permanent account self-exclusion at any time.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <HelpCircle size={16} className="text-cyan-400" />
                            3. Independent Support Organizations
                        </h2>
                        <p>
                            If you or someone you know is experiencing difficulties controlling their gaming habits, we encourage you to seek free, confidential guidance from specialized organizations:
                        </p>
                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-1">
                            <a 
                                href="https://www.ncpgambling.org" 
                                target="_blank" 
                                rel="noreferrer"
                                className="p-3.5 rounded-xl bg-[#1a1425] border border-[#3d2c4e] hover:border-yellow-400/50 flex items-center justify-between text-gray-300 hover:text-white transition"
                            >
                                <span>National Council on Problem Gambling (NCPG)</span>
                                <ExternalLink size={14} className="text-yellow-400 flex-shrink-0" />
                            </a>
                            <a 
                                href="https://www.gamblingtherapy.org" 
                                target="_blank" 
                                rel="noreferrer"
                                className="p-3.5 rounded-xl bg-[#1a1425] border border-[#3d2c4e] hover:border-yellow-400/50 flex items-center justify-between text-gray-300 hover:text-white transition"
                            >
                                <span>Gambling Therapy International</span>
                                <ExternalLink size={14} className="text-yellow-400 flex-shrink-0" />
                            </a>
                        </div>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Mail size={16} className="text-pink-400" />
                            4. Request Support or Exclusion
                        </h2>
                        <p>
                            To activate self-exclusion or request assistance, contact our Responsible Gaming team directly at <a href={`mailto:${companyEmail}`} className="text-yellow-400 font-bold hover:underline font-mono">{companyEmail}</a> or via <Link href="/contact" className="text-purple-400 font-bold hover:underline">Contact Support</Link>.
                        </p>
                    </div>

                </div>
            </div>
        </MainLayout>
    );
}
