import React from 'react';
import { useForm } from '@inertiajs/react';
import { ShieldCheck, FileCheck2, Camera, LockKeyhole } from 'lucide-react';
export default function VerificationPanel({ verification }) {
    const form = useForm({ document: null, selfie: null });
    return <>
        <div className="w-full flex flex-col gap-6">
            <div><span className="nw-eyebrow text-purple-300"><ShieldCheck size={14} /> YOUR ACCOUNT · VERIFICATION</span><h2 className="text-3xl sm:text-4xl font-bold mt-3">A secure step forward.</h2><p className="text-gray-400 mt-3">Upload your identity document and a clear selfie. Our team reviews both privately.</p></div>
            <div className="rounded-2xl border border-[#35303f] bg-[#14161f] p-6 flex flex-col gap-3" role="status"><span className="nw-eyebrow text-amber-200">REVIEW STATUS</span><strong className="text-xl capitalize">{verification?.status || 'Not submitted'}</strong>{verification?.reason && <p className="text-red-300">{verification.reason}</p>}<p className="text-sm text-gray-400">{verification?.status === 'approved' ? 'Both files are approved. Withdrawals are coming soon.' : 'Both files must be approved before the withdrawal entry appears. Replacing either file starts a new review.'}</p></div>
            <form onSubmit={e => { e.preventDefault(); form.post('/verification', { forceFormData: true, onSuccess: () => form.reset() }); }} className="flex flex-col gap-6">
                <div className="grid sm:grid-cols-2 gap-5">{[['document', 'Identity document', FileCheck2, 'Passport or national identity card. All edges and details must be readable. JPG, PNG or PDF; up to 10 MB.'], ['selfie', 'Your selfie', Camera, 'Take a recent photo of your face in good lighting, without sunglasses or filters. JPG or PNG; up to 10 MB.']].map(([kind, label, Icon, help]) => <section key={kind} className="rounded-2xl bg-[#14161f] border border-[#292635] p-6 flex flex-col gap-4"><Icon className="text-purple-300" size={28} /><label htmlFor={kind} className="font-bold text-lg">{label}</label><p className="text-sm text-gray-400 flex-1">{help}</p>{verification?.[`has_${kind}`] && <a className="text-purple-300 text-sm underline" href={`/verification/${verification.id}/${kind}`}>Download current {kind}</a>}<input id={kind} type="file" accept={kind === 'document' ? 'image/jpeg,image/png,application/pdf' : 'image/jpeg,image/png'} onChange={e => form.setData(kind, e.target.files[0])} className="text-xs w-full file:mr-3 file:rounded-lg file:border-0 file:bg-purple-500/20 file:px-3 file:py-2 file:text-purple-200" />{form.errors[kind] && <p role="alert" className="text-red-300 text-sm">{form.errors[kind]}</p>}</section>)}</div>
                {form.progress && <progress aria-label="Upload progress" className="w-full" max="100" value={form.progress.percentage} />}
                <div className="flex flex-wrap items-center gap-4"><button className="nw-button nw-button-purple" disabled={form.processing}>{form.processing ? 'Uploading securely…' : 'Upload for review'}</button><span className="text-xs text-gray-500 flex items-center gap-2"><LockKeyhole size={14} /> Accessible only to you and authorized reviewers</span></div>
            </form>
        </div>
    </>;
}
