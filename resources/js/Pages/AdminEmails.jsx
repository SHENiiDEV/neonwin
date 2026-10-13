import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import { Mail, ShieldCheck } from 'lucide-react';
import AdminLayout from '../Layouts/AdminLayout';

const previews = [
    { key: 'welcome', label: 'Welcome', detail: 'Sent after a new account is created.' },
    { key: 'deposit', label: 'Deposit confirmed', detail: 'Queued only after a verified EUR payment credits the wallet.' },
    { key: 'newsletter', label: 'Newsletter', detail: 'Design preview only. Campaign sending is not enabled.' },
];

export default function AdminEmails() {
    const [selected, setSelected] = useState('welcome');

    return <AdminLayout title="Email previews" description="Review the current player emails at desktop and mobile widths before they are sent."><Head title="Admin · Email previews" />
        <div className="grid gap-6 xl:grid-cols-[280px_minmax(0,1fr)]">
            <aside className="space-y-3">{previews.map(preview => <button key={preview.key} type="button" onClick={() => setSelected(preview.key)} aria-pressed={selected === preview.key} className={`w-full rounded-2xl border p-5 text-left transition-colors ${selected === preview.key ? 'border-purple-400/50 bg-purple-500/10' : 'border-white/10 bg-[#17141f] hover:border-purple-400/30'}`}><span className="flex items-center gap-2 text-sm font-semibold"><Mail size={16} className="text-purple-300" />{preview.label}</span><span className="mt-2 block text-xs leading-5 text-gray-400">{preview.detail}</span></button>)}<p className="flex gap-2 px-2 pt-3 text-xs leading-5 text-amber-200"><ShieldCheck size={16} className="shrink-0" />Newsletter sending remains disabled. Future campaigns must use each player's marketing email preference.</p></aside>
            <section className="min-w-0 overflow-hidden rounded-2xl border border-white/10 bg-[#0d0f16]"><div className="flex items-center justify-between border-b border-white/10 px-5 py-4"><div><h2 className="font-semibold">{previews.find(preview => preview.key === selected)?.label}</h2><p className="mt-1 text-xs text-gray-500">Preview uses sample data and sends no email.</p></div><a href={`/admin/emails/preview/${selected}`} target="_blank" rel="noreferrer" className="text-xs text-purple-300 underline">Open full size</a></div><iframe key={selected} title={`${selected} email preview`} src={`/admin/emails/preview/${selected}`} className="h-[980px] w-full border-0" /></section>
        </div>
    </AdminLayout>;
}
