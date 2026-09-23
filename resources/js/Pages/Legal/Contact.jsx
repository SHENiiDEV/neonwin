import React, { useState } from 'react';
import { Head, useForm, usePage, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    Mail, 
    MessageSquare, 
    Send, 
    Sparkles, 
    ShieldCheck, 
    Clock, 
    CheckCircle2, 
    MapPin, 
    PhoneCall, 
    AlertCircle, 
    Loader2,
    Headphones,
    HelpCircle,
    ArrowRight
} from 'lucide-react';

export default function Contact() {
    const { company, flash } = usePage().props;

    const companyName = company?.name || 'Neonwin Interactive Ltd.';
    const companyNumber = company?.number || 'HE 448921';
    const companyAddress = company?.address || 'Agiou Pavlou 15, Ledra Business Center, Block B, Office 302, 1105 Nicosia, Cyprus';
    const companyEmail = company?.email || 'support@neonwin.com';

    const { data, setData, post, processing, errors, reset, recentlySuccessful } = useForm({
        name: '',
        email: '',
        category: 'general',
        subject: '',
        message: '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/contact', {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <MainLayout>
            {({ openChat }) => (
                <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                    <Head title="Contact Support & 24/7 Concierge — Neonwin" />

                    {/* Sub-nav */}
                    <LegalNav currentPath="/contact" />

                    {/* Hero Header */}
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                        <div className="max-w-2xl relative z-10 text-center md:text-left">
                            <span className="nw-eyebrow mb-2 text-yellow-400">
                                <Headphones size={13} /> 24/7 DEDICATED SUPPORT
                            </span>
                            <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-3">
                                We're here to <br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-amber-200 to-yellow-500">
                                    Help You Win.
                                </span>
                            </h1>
                            <p className="text-xs sm:text-sm text-gray-300 leading-relaxed font-medium max-w-xl">
                                Have questions regarding your Sweeps Coins, verification, game mechanics, or VIP status? Reach out anytime.
                            </p>
                        </div>

                        {/* Quick Stats Pill */}
                        <div className="flex flex-col gap-2.5 p-4 rounded-2xl bg-[#1d1627] border border-[#3e2e4e] text-xs">
                            <div className="flex items-center gap-2 text-emerald-400 font-bold">
                                <span className="w-2 h-2 rounded-full bg-emerald-400 animate-ping" />
                                <span>Live Agents Online Now</span>
                            </div>
                            <div className="text-gray-400 font-mono text-[11px]">
                                Average response time: <strong className="text-white">&lt; 90 seconds</strong>
                            </div>
                        </div>
                    </div>

                    {/* 2-Column Contact Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                        
                        {/* Left Column: Direct Support Channels */}
                        <div className="lg:col-span-5 flex flex-col gap-5">
                            
                            {/* Fast Ticket & Support Card */}
                            <div className="p-6 rounded-3xl bg-gradient-to-br from-[#1e1531] to-[#120f1c] border border-purple-500/40 shadow-xl flex flex-col justify-between relative overflow-hidden">
                                <div className="space-y-3">
                                    <div className="w-12 h-12 rounded-2xl bg-purple-600/30 border border-purple-500/50 flex items-center justify-center text-yellow-400 shadow-inner">
                                        <Headphones size={24} />
                                    </div>
                                    <h3 className="font-heading font-black text-lg text-white">24/7 VIP Concierge & Help</h3>
                                    <p className="text-xs text-gray-300 leading-relaxed">
                                        Submit a support request directly using the form on this page. Every ticket is assigned to a dedicated specialist with priority resolution for VIP players.
                                    </p>
                                </div>

                                <div className="mt-5 p-3.5 rounded-2xl bg-[#140e22] border border-purple-500/20 text-xs text-purple-300 flex items-center gap-2">
                                    <Sparkles size={14} className="text-yellow-400 flex-shrink-0" />
                                    <span>Average response time under 2 hours</span>
                                </div>
                            </div>

                            {/* Email Card */}
                            <div className="p-6 rounded-3xl bg-[#14111d] border border-[#342742] space-y-3">
                                <div className="w-10 h-10 rounded-xl bg-amber-900/40 border border-amber-500/40 flex items-center justify-center text-yellow-400">
                                    <Mail size={20} />
                                </div>
                                <h3 className="font-heading font-bold text-base text-white">Email Inquiries</h3>
                                <p className="text-xs text-gray-400 leading-relaxed">
                                    For general support, partnerships, media inquiries, or compliance documentation:
                                </p>
                                <a 
                                    href={`mailto:${companyEmail}`}
                                    className="inline-block text-xs font-mono font-bold text-yellow-400 hover:underline pt-1"
                                >
                                    {companyEmail}
                                </a>
                            </div>

                            {/* Corporate Info */}
                            <div className="p-6 rounded-3xl bg-[#14111d] border border-[#342742] space-y-3 text-xs text-gray-400">
                                <div className="flex items-center gap-2 text-white font-bold font-heading text-sm">
                                    <MapPin size={16} className="text-purple-400" />
                                    <span>Registered Office</span>
                                </div>
                                <p className="leading-relaxed">
                                    <strong className="text-white">{companyName}</strong><br />
                                    Reg. No: {companyNumber}<br />
                                    {companyAddress}
                                </p>
                            </div>
                        </div>

                        {/* Right Column: Interactive Contact Form */}
                        <div className="lg:col-span-7 p-6 sm:p-8 rounded-3xl bg-[#151220] border border-[#372846] shadow-xl flex flex-col justify-between">
                            <div>
                                <div className="flex items-center justify-between mb-6">
                                    <div>
                                        <h2 className="font-heading font-black text-xl text-white">Send Us a Message</h2>
                                        <p className="text-xs text-gray-400 mt-0.5">We typically respond within 2 to 4 hours via email</p>
                                    </div>
                                    <span className="text-[10px] text-purple-300 font-mono bg-purple-950/60 border border-purple-800/40 px-3 py-1 rounded-full">
                                        SECURE TICKET
                                    </span>
                                </div>

                                {flash?.success && (
                                    <div className="mb-6 p-4 rounded-2xl bg-emerald-950/80 border border-emerald-500/50 text-emerald-200 text-xs font-bold flex items-center gap-3">
                                        <CheckCircle2 size={18} className="text-emerald-400 flex-shrink-0" />
                                        <span>{flash.success}</span>
                                    </div>
                                )}

                                <form onSubmit={handleSubmit} className="space-y-4">
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        {/* Name */}
                                        <div className="space-y-1.5">
                                            <label className="text-xs font-semibold text-gray-300 block">Your Name / Username</label>
                                            <input 
                                                type="text"
                                                required
                                                value={data.name}
                                                onChange={e => setData('name', e.target.value)}
                                                placeholder="e.g. CyberPlayer"
                                                className="w-full px-4 py-2.5 rounded-xl bg-[#1d1728] border border-[#443355] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                            />
                                            {errors.name && <p className="text-[10px] text-rose-400">{errors.name}</p>}
                                        </div>

                                        {/* Email */}
                                        <div className="space-y-1.5">
                                            <label className="text-xs font-semibold text-gray-300 block">Your Email Address</label>
                                            <input 
                                                type="email"
                                                required
                                                value={data.email}
                                                onChange={e => setData('email', e.target.value)}
                                                placeholder="player@example.com"
                                                className="w-full px-4 py-2.5 rounded-xl bg-[#1d1728] border border-[#443355] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                            />
                                            {errors.email && <p className="text-[10px] text-rose-400">{errors.email}</p>}
                                        </div>
                                    </div>

                                    {/* Category */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-semibold text-gray-300 block">Inquiry Category</label>
                                        <select 
                                            value={data.category}
                                            onChange={e => setData('category', e.target.value)}
                                            className="w-full px-4 py-2.5 rounded-xl bg-[#1d1728] border border-[#443355] text-white text-xs focus:outline-none focus:border-purple-400"
                                        >
                                            <option value="general">General Inquiries & Questions</option>
                                            <option value="sweeps">Sweeps Coins & Daily Rewards</option>
                                            <option value="verification">KYC & Account Verification</option>
                                            <option value="gameplay">Game Mechanics & Technical Help</option>
                                            <option value="vip">VIP Club & Concierge</option>
                                            <option value="compliance">Legal & Responsible Gaming</option>
                                        </select>
                                        {errors.category && <p className="text-[10px] text-rose-400">{errors.category}</p>}
                                    </div>

                                    {/* Subject */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-semibold text-gray-300 block">Subject</label>
                                        <input 
                                            type="text"
                                            required
                                            value={data.subject}
                                            onChange={e => setData('subject', e.target.value)}
                                            placeholder="Brief summary of your inquiry"
                                            className="w-full px-4 py-2.5 rounded-xl bg-[#1d1728] border border-[#443355] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400"
                                        />
                                        {errors.subject && <p className="text-[10px] text-rose-400">{errors.subject}</p>}
                                    </div>

                                    {/* Message */}
                                    <div className="space-y-1.5">
                                        <label className="text-xs font-semibold text-gray-300 block">Message Details</label>
                                        <textarea 
                                            required
                                            rows={5}
                                            value={data.message}
                                            onChange={e => setData('message', e.target.value)}
                                            placeholder="Please explain in detail how we can assist you..."
                                            className="w-full px-4 py-2.5 rounded-xl bg-[#1d1728] border border-[#443355] text-white placeholder-gray-500 text-xs focus:outline-none focus:border-purple-400 resize-y"
                                        />
                                        {errors.message && <p className="text-[10px] text-rose-400">{errors.message}</p>}
                                    </div>

                                    {/* Submit Button */}
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full nw-button nw-button-purple py-3 text-xs font-bold flex items-center justify-center gap-2"
                                    >
                                        {processing ? (
                                            <>
                                                <Loader2 size={16} className="animate-spin" />
                                                <span>Sending message...</span>
                                            </>
                                        ) : (
                                            <>
                                                <Send size={15} />
                                                <span>Send Support Message</span>
                                            </>
                                        )}
                                    </button>
                                </form>
                            </div>

                            <div className="mt-6 pt-4 border-t border-white/5 flex items-center justify-between text-[10px] text-gray-500">
                                <span>🔒 256-bit SSL Encrypted Ticket Submission</span>
                                <Link href="/privacy" className="hover:text-purple-300">Privacy Policy</Link>
                            </div>
                        </div>

                    </div>
                </div>
            )}
        </MainLayout>
    );
}
