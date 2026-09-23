import React from 'react';
import { Head, usePage, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    Shield, 
    FileText, 
    CheckCircle2, 
    AlertCircle, 
    Lock, 
    Coins, 
    Building2, 
    Mail,
    Scale,
    UserCheck,
    Gamepad2,
    Clock
} from 'lucide-react';

export default function Terms() {
    const { company } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyNumber = company?.number || 'HE 448921';
    const companyAddress = company?.address || 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus';
    const companyEmail = company?.email || 'support@neonwin.com';

    return (
        <MainLayout>
            <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                <Head title="Terms of Service — Neonwin Social Casino" />

                {/* Sub-nav */}
                <LegalNav currentPath="/terms" />

                {/* Header Banner */}
                <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                    <div className="max-w-2xl relative z-10 text-center md:text-left">
                        <span className="nw-eyebrow mb-2 text-yellow-400">
                            <Scale size={13} /> OFFICIAL USER AGREEMENT
                        </span>
                        <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-2">
                            Terms of Service
                        </h1>
                        <p className="text-xs sm:text-sm text-gray-300 font-medium">
                            Effective Date: September 2026 • Version 3.1
                        </p>
                    </div>

                    <div className="p-4 rounded-2xl bg-[#1d1627] border border-[#3e2e4e] text-xs text-gray-400 space-y-1">
                        <div className="text-white font-bold flex items-center gap-1.5">
                            <Clock size={14} className="text-yellow-400" />
                            <span>Last Revised</span>
                        </div>
                        <p className="font-mono text-[11px]">September 21, 2026</p>
                    </div>
                </div>

                {/* Main Legal Content */}
                <div className="p-6 sm:p-10 rounded-3xl bg-[#14101e] border border-[#342742] space-y-8 text-xs text-gray-300 leading-relaxed shadow-xl">
                    
                    {/* Section 1 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Building2 size={16} className="text-yellow-400" />
                            1. Entity & Acceptance of the Agreement
                        </h2>
                        <p>
                            These Terms of Service constitute a legally binding agreement entered into by and between you (the "Player", "User", or "You") and <strong className="text-white">{companyName}</strong>, a company registered in Cyprus under Registration Number <strong className="text-white">{companyNumber}</strong>, having its registered seat at <strong className="text-white">{companyAddress}</strong> ("Company", "We", "Us", or "Neonwin").
                        </p>
                        <p>
                            By registering an account, accessing, or playing on the Neonwin platform (website, web applications, or mobile interfaces), you explicitly certify that you have read, understood, and agreed to be legally bound by these Terms of Service, our Privacy Policy, and our Sweeps Rules.
                        </p>
                    </div>

                    {/* Section 2 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <UserCheck size={16} className="text-purple-400" />
                            2. Player Eligibility & Age Verification (18+)
                        </h2>
                        <p>
                            Access to Neonwin is strictly restricted to natural persons who are at least eighteen (18) years of age, or the legal age of majority applicable in their jurisdiction of residence. By creating an account, you warrant and represent that:
                        </p>
                        <ul className="list-disc pl-5 space-y-1 text-gray-400">
                            <li>You are 18 years of age or older;</li>
                            <li>You are participating solely in your personal, non-commercial entertainment capacity;</li>
                            <li>You are not located in any restricted territories where social casino sweepstakes are prohibited by local law;</li>
                            <li>You have provided accurate, truthful, and up-to-date registration information.</li>
                        </ul>
                    </div>

                    {/* Section 3 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Coins size={16} className="text-yellow-400" />
                            3. Virtual Currency (Sweeps Coins - SC) & No Purchase Necessary
                        </h2>
                        <p>
                            Neonwin operates as a promotional Social Gaming platform. The virtual currency utilized on the platform is designated as Sweeps Coins ("SC").
                        </p>
                        <ul className="list-disc pl-5 space-y-1 text-gray-400">
                            <li><strong className="text-white">No Purchase Necessary:</strong> No purchase of any kind is required to open an account, access the lobby, or obtain Sweeps Coins.</li>
                            <li><strong className="text-white">Free Acquisition:</strong> Free Sweeps Coins are made available through daily login claims (0.02 SC/day), social promotions, VIP rank-up gifts, and postal mail-in requests.</li>
                            <li><strong className="text-white">Entertainment Value:</strong> Sweeps Coins have no direct real-world commercial value and cannot be directly bought or traded between players.</li>
                        </ul>
                    </div>

                    {/* Section 4 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Gamepad2 size={16} className="text-cyan-400" />
                            4. Certified Games & Nexus Aggregator Integration
                        </h2>
                        <p>
                            All games displayed on Neonwin are aggregated and rendered via licensed gaming servers (NexusGGR). Game outcomes are determined purely by certified Random Number Generators (RNG) evaluated and certified by independent testing laboratories. The company does not alter math models, RTP percentages, or random algorithms.
                        </p>
                    </div>

                    {/* Section 5 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Shield size={16} className="text-emerald-400" />
                            5. Anti-Fraud, Multi-Accounting & Prohibited Conduct
                        </h2>
                        <p>
                            Each user is permitted only one (1) unique account per person, household, IP address, and device. Any player engaging in multi-accounting, automated bots, exploit scripts, or fraudulent activity will be immediately banned and all associated balances forfeited.
                        </p>
                    </div>

                    {/* Section 6 */}
                    <div className="space-y-3">
                        <h2 className="text-sm font-heading font-black text-white uppercase tracking-wider flex items-center gap-2">
                            <Mail size={16} className="text-pink-400" />
                            6. Inquiries, Notices & Dispute Resolution
                        </h2>
                        <p>
                            For inquiries regarding these Terms, dispute escalation, or formal legal notices, please contact our Legal Department at:
                        </p>
                        <div className="p-4 rounded-xl bg-[#191325] border border-[#3b2d49] flex items-center gap-3">
                            <Mail size={18} className="text-yellow-400 flex-shrink-0" />
                            <div>
                                <span className="text-white font-bold">{companyName} — Legal & Compliance</span><br />
                                <a href={`mailto:${companyEmail}`} className="text-purple-400 hover:underline font-mono">{companyEmail}</a>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </MainLayout>
    );
}
