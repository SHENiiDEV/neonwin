import React, { useState, useMemo } from 'react';
import { Head, Link } from '@inertiajs/react';
import MainLayout from '../../Layouts/MainLayout';
import LegalNav from '../../Components/LegalNav';
import { 
    HelpCircle, 
    ChevronDown, 
    Search, 
    Sparkles, 
    ShieldCheck, 
    Coins, 
    Trophy, 
    Gamepad2, 
    Headphones, 
    ArrowRight,
    X
} from 'lucide-react';

const faqCategories = [
    { id: 'all', name: 'All Questions', icon: HelpCircle },
    { id: 'sweeps', name: 'Sweeps & SC Coins', icon: Coins },
    { id: 'games', name: 'Games & Provably Fair', icon: Gamepad2 },
    { id: 'vip', name: 'VIP Club & Rewards', icon: Trophy },
    { id: 'account', name: 'Account & Security', icon: ShieldCheck },
];

const faqsData = [
    {
        category: 'sweeps',
        q: 'What are Sweeps Coins (SC) and how do they work?',
        a: 'Sweeps Coins (SC) are our virtual currency used for promotional social gameplay across all our slot games, live casino tables, and crash titles. Sweeps Coins are given away for free via daily login bonuses, community promotions, or as complimentary perks with entertainment packs.'
    },
    {
        category: 'sweeps',
        q: 'How can I get free Sweeps Coins without any purchase?',
        a: 'You can claim 50 free SC every 24 hours from your Top-Up modal, participate in our social giveaways, level up your VIP rank to receive rank milestone rewards, and participate in community multiplier tournaments.'
    },
    {
        category: 'sweeps',
        q: 'Is any purchase necessary to play on Neonwin?',
        a: 'No! Neonwin operates strictly as a Social Casino. No purchase is necessary to register, play games, or participate in promotional sweepstakes.'
    },
    {
        category: 'games',
        q: 'Are the game outcomes fair and certified?',
        a: 'Yes, 100%. All games are hosted and rendered directly through official licensed game providers (Pragmatic Play, PG Soft, Hacksaw Gaming, Spribe, etc.) via NexusGGR. Every spin, dice roll, and multiplier relies on certified Random Number Generators (RNG) with fixed public RTP percentages.'
    },
    {
        category: 'games',
        q: 'What game categories are available?',
        a: 'Neonwin features over 1,500 games including Video Slots, Megaways, Cluster Pays, Live Dealer Roulette & Blackjack, Crash & Fast Mini-Games (Aviator, Mines), and Fish Hunter arcade titles.'
    },
    {
        category: 'vip',
        q: 'How does the VIP Club work?',
        a: 'Every 1 SC wagered automatically grants 1 VIP XP point in real time. As your XP grows, you progress through 5 elite tiers (Bronze Recruit, Silver Shadow, Gold Ronin, Platinum Cyber, Neon Overlord), unlocking higher instant cashback up to 18%, weekly rakeback, and level-up cash gifts.'
    },
    {
        category: 'vip',
        q: 'Do VIP cashback bonuses have wagering requirements?',
        a: 'None! All VIP rewards (Cashback, Rakeback, Level Bonus Drops) carry a 0x wagering requirement — they are credited directly as pure SC to your wallet.'
    },
    {
        category: 'account',
        q: 'How do I secure my account?',
        a: 'We use 256-bit SSL encryption for all data transmissions. We recommend using a strong unique password. Account verification (KYC) is available in your profile settings to ensure fast and secure redemption processing.'
    },
    {
        category: 'account',
        q: 'Can I have multiple accounts?',
        a: 'No. To ensure fair play and prevent sweepstakes manipulation, each individual is strictly limited to one (1) registered account. Duplicate accounts are automatically flagged and closed.'
    },
];

export default function Faq() {
    const [selectedCategory, setSelectedCategory] = useState('all');
    const [searchQuery, setSearchQuery] = useState('');
    const [openIndex, setOpenIndex] = useState(null);

    const filteredFaqs = useMemo(() => {
        return faqsData.filter(item => {
            const matchesCategory = selectedCategory === 'all' || item.category === selectedCategory;
            const matchesSearch = !searchQuery || 
                item.q.toLowerCase().includes(searchQuery.toLowerCase()) || 
                item.a.toLowerCase().includes(searchQuery.toLowerCase());
            return matchesCategory && matchesSearch;
        });
    }, [selectedCategory, searchQuery]);

    const toggleAccordion = (idx) => {
        setOpenIndex(openIndex === idx ? null : idx);
    };

    return (
        <MainLayout>
            {({ openChat }) => (
                <div className="w-full max-w-[1536px] mx-auto flex flex-col gap-8 py-4">
                    <Head title="Help & Frequently Asked Questions — Neonwin" />

                    {/* Sub-navigation */}
                    <LegalNav currentPath="/faq" />

                    {/* Hero Header */}
                    <div className="relative rounded-3xl overflow-hidden bg-gradient-to-r from-[#1c122c] via-[#14101e] to-[#0d0f16] border border-[#483450] p-8 sm:p-12 flex flex-col md:flex-row items-center justify-between gap-6 shadow-[0_20px_60px_rgba(0,0,0,0.6)]">
                        <div className="max-w-2xl relative z-10 text-center md:text-left">
                            <span className="nw-eyebrow mb-2 text-yellow-400">
                                <HelpCircle size={13} /> KNOWLEDGE BASE & HELP
                            </span>
                            <h1 className="font-heading font-black text-3xl sm:text-4xl md:text-5xl text-white tracking-tight leading-tight mb-3">
                                Frequently Asked <br />
                                <span className="text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-amber-200 to-yellow-500">
                                    Questions.
                                </span>
                            </h1>
                            <p className="text-xs sm:text-sm text-gray-300 leading-relaxed font-medium max-w-xl">
                                Find clear answers to common questions about our games, virtual Sweeps Coins, VIP rewards, and security.
                            </p>
                        </div>

                        {/* Search Bar in Hero */}
                        <div className="w-full md:w-80 relative">
                            <Search className="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                            <input 
                                type="text"
                                value={searchQuery}
                                onChange={e => setSearchQuery(e.target.value)}
                                placeholder="Search questions..."
                                className="w-full pl-10 pr-9 py-3 rounded-2xl bg-[#1d1728] border border-[#4b375b] text-white placeholder-gray-400 text-xs focus:outline-none focus:border-yellow-400/60 shadow-inner"
                            />
                            {searchQuery && (
                                <button 
                                    onClick={() => setSearchQuery('')}
                                    className="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white"
                                >
                                    <X size={14} />
                                </button>
                            )}
                        </div>
                    </div>

                    {/* Category Filter Pills */}
                    <div className="flex items-center gap-2 overflow-x-auto no-scrollbar pb-1">
                        {faqCategories.map(cat => {
                            const Icon = cat.icon;
                            const isSelected = selectedCategory === cat.id;

                            return (
                                <button
                                    key={cat.id}
                                    onClick={() => setSelectedCategory(cat.id)}
                                    className={`flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold transition whitespace-nowrap ${
                                        isSelected
                                            ? 'bg-gradient-to-r from-purple-600 to-indigo-600 text-white shadow-neon-purple'
                                            : 'bg-[#151220] border border-[#31253e] text-gray-400 hover:text-white hover:border-purple-500/30'
                                    }`}
                                >
                                    <Icon size={14} className={isSelected ? 'text-yellow-300' : 'text-gray-500'} />
                                    <span>{cat.name}</span>
                                </button>
                            );
                        })}
                    </div>

                    {/* FAQ Accordion List */}
                    <div className="space-y-3">
                        {filteredFaqs.length > 0 ? (
                            filteredFaqs.map((faq, idx) => {
                                const isOpen = openIndex === idx;

                                return (
                                    <div 
                                        key={idx}
                                        className={`rounded-2xl border transition-all duration-200 overflow-hidden ${
                                            isOpen
                                                ? 'bg-[#181324] border-yellow-500/40 shadow-lg'
                                                : 'bg-[#13101c] border-[#31253e] hover:border-[#4d3862]'
                                        }`}
                                    >
                                        <button
                                            onClick={() => toggleAccordion(idx)}
                                            className="w-full px-5 py-4 text-left flex items-center justify-between gap-4"
                                        >
                                            <span className="font-heading font-bold text-xs sm:text-sm text-white flex items-center gap-2.5">
                                                <span className="text-yellow-400 font-mono text-xs">Q.</span>
                                                {faq.q}
                                            </span>
                                            <ChevronDown 
                                                className={`w-4 h-4 text-gray-400 transition-transform duration-200 flex-shrink-0 ${
                                                    isOpen ? 'transform rotate-180 text-yellow-400' : ''
                                                }`} 
                                            />
                                        </button>

                                        {isOpen && (
                                            <div className="px-5 pb-5 pt-1 text-xs text-gray-300 leading-relaxed border-t border-white/5 animate-in fade-in duration-150">
                                                {faq.a}
                                            </div>
                                        )}
                                    </div>
                                );
                            })
                        ) : (
                            <div className="p-12 text-center rounded-3xl bg-[#14111d] border border-[#31253e] space-y-3">
                                <HelpCircle className="w-10 h-10 text-gray-600 mx-auto" />
                                <p className="text-sm font-bold text-white">No questions matched your search criteria.</p>
                                <button 
                                    onClick={() => { setSelectedCategory('all'); setSearchQuery(''); }}
                                    className="text-xs text-purple-400 font-bold hover:underline"
                                >
                                    Reset search filters
                                </button>
                            </div>
                        )}
                    </div>

                    {/* Bottom Support Card */}
                    <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-r from-[#211536] via-[#171324] to-[#120f1b] border border-purple-500/30 flex flex-col sm:flex-row items-center justify-between gap-6">
                        <div className="flex items-center gap-4 text-center sm:text-left">
                            <div className="w-14 h-14 rounded-2xl bg-purple-600/20 border border-purple-500/40 flex items-center justify-center text-yellow-400 flex-shrink-0 shadow-inner">
                                <Headphones size={28} />
                            </div>
                            <div>
                                <h3 className="font-heading font-black text-base sm:text-lg text-white">
                                    Still have questions or need assistance?
                                </h3>
                                <p className="text-xs text-gray-300 mt-0.5">
                                    Our support specialists are available 24/7 to help you.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3 flex-shrink-0">
                            <Link
                                href="/contact"
                                className="nw-button nw-button-purple px-6 py-3 text-xs font-bold flex items-center gap-2"
                            >
                                <Sparkles size={14} />
                                <span>Submit Support Request</span>
                                <ArrowRight size={13} />
                            </Link>
                        </div>
                    </div>

                </div>
            )}
        </MainLayout>
    );
}
