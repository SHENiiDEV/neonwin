import React from 'react';
import { Head, usePage } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    Shield, 
    Lock, 
    Eye, 
    FileText, 
    Database, 
    Cookie, 
    UserCheck, 
    Mail, 
    Building2,
    Clock
} from 'lucide-react';

export default function Privacy() {
    const { company } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyNumber = company?.number || 'HE 448921';
    const companyAddress = company?.address || 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus';
    const companyEmail = company?.email || 'support@neonwin.com';

    return (
        <MainLayout>
            <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                <Head title="Privacy Policy & GDPR — Neonwin" />

                {/* Sub-nav */}
                <LegalNav currentPath="/privacy" />

                {/* Header Banner */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                    <div className="max-w-2xl relative z-10 text-center md:text-left">
                        <span className="nw-eyebrow mb-2 text-emerald-400">
                            <Shield size={13} /> DATA PROTECTION & GDPR COMPLIANT
                        </span>
                        <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-2">
                            Privacy Policy
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-300 font-medium">
                            How we protect, encrypt, and handle your personal data.
                        </p>
                    </div>

                    <div className="p-4 rounded-2xl bg-[#1d1627] border border-[#3e2e4e] text-xs text-gray-400 space-y-1">
                        <div className="text-white font-bold flex items-center gap-1.5">
                            <Lock size={14} className="text-emerald-400" />
                            <span>Encryption Standard</span>
                        </div>
                        <p className="font-mono text-[11px] text-emerald-300">256-bit SSL / TLS 1.3</p>
                    </div>
                </div>

                {/* Content */}
                <div className="p-6 sm:p-10 rounded-3xl bg-[#14101e] border border-[#342742] space-y-8 text-xs text-gray-300 leading-relaxed shadow-xl">
                    
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Building2 size={16} className="text-yellow-400" />
                            1. Data Controller
                        </h2>
                        <p>
                            This Privacy Policy sets out how <strong className="text-white">{companyName}</strong> (Cyprus Reg. <strong className="text-white">{companyNumber}</strong>, located at <strong className="text-white">{companyAddress}</strong>) collects, uses, stores, and protects personal data collected through the Neonwin platform.
                        </p>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Database size={16} className="text-purple-400" />
                            2. Information We Collect
                        </h2>
                        <p>
                            We collect personal information necessary to deliver a secure, personalized gaming experience:
                        </p>
                        <ul className="list-disc pl-5 space-y-1 text-gray-400">
                            <li><strong className="text-white">Account Information:</strong> Username, email address, password hash, avatar;</li>
                            <li><strong className="text-white">Gameplay & Transaction Data:</strong> Game launch logs, Sweeps Coins balance, VIP XP progression, tournament results;</li>
                            <li><strong className="text-white">Technical Metadata:</strong> IP address, device fingerprints, browser version, operating system, and session timestamps for anti-fraud detection;</li>
                            <li><strong className="text-white">Verification Data (KYC):</strong> Government-issued ID and address proofs when required for prize redemptions.</li>
                        </ul>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Lock size={16} className="text-cyan-400" />
                            3. How We Use and Protect Your Data
                        </h2>
                        <p>
                            We do not sell, rent, or trade your personal information to third parties. Data is used exclusively to:
                        </p>
                        <ul className="list-disc pl-5 space-y-1 text-gray-400">
                            <li>Operate game sessions and synchronize wallet balances with our certified aggregator;</li>
                            <li>Prevent fraud, multi-accounting, and unauthorized access;</li>
                            <li>Calculate and award VIP cashback, weekly rakeback, and leaderboard rankings;</li>
                            <li>Comply with statutory legal obligations and age verification mandates.</li>
                        </ul>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <UserCheck size={16} className="text-emerald-400" />
                            4. Your GDPR Rights
                        </h2>
                        <p>
                            Under the European Union General Data Protection Regulation (GDPR), you possess the right to:
                        </p>
                        <ul className="list-disc pl-5 space-y-1 text-gray-400">
                            <li>Request access to copies of your personal data;</li>
                            <li>Request rectification of inaccurate information;</li>
                            <li>Request erasure ("Right to be forgotten") of your account and personal details;</li>
                            <li>Object to or restrict the processing of your data.</li>
                        </ul>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Mail size={16} className="text-pink-400" />
                            5. Data Protection Officer (DPO) Contact
                        </h2>
                        <p>
                            To exercise any of your data rights or contact our Privacy Officer, email us at:
                        </p>
                        <div className="p-4 rounded-xl bg-[#191325] border border-[#3b2d49] flex items-center gap-3">
                            <Mail size={18} className="text-yellow-400 flex-shrink-0" />
                            <div>
                                <span className="text-white font-bold">Data Privacy & Compliance Office</span><br />
                                <a href={`mailto:${companyEmail}`} className="text-purple-400 hover:underline font-mono">{companyEmail}</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </MainLayout>
    );
}
