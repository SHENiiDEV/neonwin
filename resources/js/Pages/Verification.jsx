import React from 'react';
import { Head } from '@inertiajs/react';
import MainLayout from '../Layouts/MainLayout';
import VerificationPanel from '../Components/VerificationPanel';
export default function Verification({ verification }) {
 return <MainLayout><Head title="Identity verification" /><div className="max-w-6xl mx-auto py-6"><VerificationPanel verification={verification} /></div></MainLayout>;
}
