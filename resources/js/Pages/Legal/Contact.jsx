import React from 'react';
import { usePage } from '@inertiajs/react';
import LegalPage from '../../Components/LegalPage';
export default function Contact() {
    const { company } = usePage().props;
    const email = company?.email;
    return <LegalPage title="Contact support" path="/contact" description="Account, wallet, game or privacy question? Give us the details so we can look into it." sections={[
        ['Account and game questions', ['Include your player code, the game or provider name, the approximate time and a transaction reference if available. We do not publish guaranteed response times.']],
        ['Payment and verification questions', ['Payment processing and withdrawals are currently unavailable. Use the Verification section of your profile to upload private documents. Never attach identity documents, passwords, vault PINs or full card details to a general support message.']],
        ['Privacy and responsible play', ['For a privacy request or account restriction, explain the action you need. An account restriction takes effect only after it has been confirmed.']]
    ]}>{email ? <a href={`mailto:${email}`} className="nw-button nw-button-purple self-start">Email {email}</a> : <p className="text-gray-400 text-sm">A support address has not been published yet.</p>}</LegalPage>;
}
