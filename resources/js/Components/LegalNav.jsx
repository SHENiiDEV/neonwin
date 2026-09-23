import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { 
    FileText, 
    Shield, 
    Sparkles, 
    HelpCircle, 
    Mail, 
    HeartHandshake, 
    Trophy,
    Flame
} from 'lucide-react';

const legalLinks = [
    { name: 'About Neonwin', href: '/about', icon: Sparkles },
    { name: 'Terms of Service', href: '/terms', icon: FileText },
    { name: 'Privacy Policy', href: '/privacy', icon: Shield },
    { name: 'Sweeps Rules', href: '/sweeps-rules', icon: Trophy },
    { name: 'Responsible Gaming', href: '/responsible-gaming', icon: HeartHandshake },
    { name: 'Help & FAQ', href: '/faq', icon: HelpCircle },
    { name: 'Contact Support', href: '/contact', icon: Mail },
];

export default function LegalNav({ currentPath = '' }) {
    const { url } = usePage();
    const activeUrl = currentPath || url;

    return (
        <nav className="flex items-center gap-1.5 p-1.5 rounded-2xl bg-[#14101e] border border-[#342642] overflow-x-auto no-scrollbar shadow-inner" aria-label="Legal navigation">
            {legalLinks.map((item) => {
                const Icon = item.icon;
                const isActive = activeUrl.startsWith(item.href);

                return (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={`flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold whitespace-nowrap transition-all ${
                            isActive
                                ? 'bg-gradient-to-r from-purple-900/90 to-indigo-900/90 text-yellow-300 border border-purple-500/50 shadow-[0_0_15px_rgba(168,85,247,0.25)]'
                                : 'text-gray-400 hover:text-white hover:bg-white/5'
                        }`}
                    >
                        <Icon className={`w-3.5 h-3.5 ${isActive ? 'text-yellow-400' : 'text-gray-500'}`} />
                        <span>{item.name}</span>
                    </Link>
                );
            })}
        </nav>
    );
}
