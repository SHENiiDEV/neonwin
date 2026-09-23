import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Headphones, ArrowRight, HelpCircle, Mail } from 'lucide-react';

export default function ContactBanner() {
    const { company } = usePage().props;
    return (
        <section className="nw-contact">
            <div className="nw-contact-icon">
                <Headphones size={38} strokeWidth={1.5} />
            </div>
            <div className="nw-contact-copy">
                <span className="nw-eyebrow">24/7 PLAYER ASSISTANCE</span>
                <h2>A little help? We’re here.</h2>
                <p>
                    Browse answers in our <Link href="/faq" className="text-purple-300 font-bold hover:underline">Help & FAQ center</Link> or send a message to our support specialists.
                </p>
            </div>
            <div className="nw-contact-actions">
                <Link href="/contact" className="nw-button nw-button-purple">
                    <span>Contact Support</span>
                    <ArrowRight size={15} />
                </Link>
                <Link href="/faq" className="text-gray-300 hover:text-white flex items-center gap-1.5 text-xs font-semibold">
                    <HelpCircle size={15} className="text-yellow-400" />
                    <span>View FAQs</span>
                </Link>
            </div>
        </section>
    );
}
