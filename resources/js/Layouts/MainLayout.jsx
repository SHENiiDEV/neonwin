import React, { useEffect, useState } from 'react';
import { usePage, router } from '@inertiajs/react';
import Sidebar from '../Components/Sidebar';
import Header from '../Components/Header';
import Footer from '../Components/Footer';
import AuthModal from '../Components/Modals/AuthModal';
import DepositModal from '../Components/Modals/DepositModal';
import VipModal from '../Components/Modals/VipModal';
import CratesModal from '../Components/Modals/CratesModal';
import VaultModal from '../Components/Modals/VaultModal';
import TipModal from '../Components/Modals/TipModal';

export default function MainLayout({ children, highlights = [] }) {
    const { flash, auth, filters } = usePage().props;

    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [authModalOpen, setAuthModalOpen] = useState(false);
    const [authMode, setAuthMode] = useState('login');
    const [depositModalOpen, setDepositModalOpen] = useState(false);
    const [vipModalOpen, setVipModalOpen] = useState(false);
    const [cratesModalOpen, setCratesModalOpen] = useState(false);
    const [vaultModalOpen, setVaultModalOpen] = useState(false);
    const [tipModalOpen, setTipModalOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState(filters?.search || '');

    useEffect(() => { 
        setSearchQuery(filters?.search || ''); 
    }, [filters?.search]);

    useEffect(() => {
        const onEscape = event => {
            if (event.key === 'Escape') {
                setSidebarOpen(false); 
                setAuthModalOpen(false); 
                setDepositModalOpen(false); 
                setVipModalOpen(false);
                setCratesModalOpen(false);
                setVaultModalOpen(false);
                setTipModalOpen(false);
            }
        };

        const handleCustomOpenAuth = event => {
            const mode = event.detail?.mode || 'register';
            setAuthMode(mode);
            setAuthModalOpen(true);
        };

        document.addEventListener('keydown', onEscape);
        window.addEventListener('open-auth-modal', handleCustomOpenAuth);

        return () => {
            document.removeEventListener('keydown', onEscape);
            window.removeEventListener('open-auth-modal', handleCustomOpenAuth);
        };
    }, []);

    const openAuth = (mode = 'register') => { setAuthMode(mode); setAuthModalOpen(true); };
    const openDeposit = () => auth?.user ? setDepositModalOpen(true) : openAuth();
    const openCrates = () => auth?.user ? setCratesModalOpen(true) : openAuth();
    const openVault = () => auth?.user ? setVaultModalOpen(true) : openAuth();
    const openTip = () => auth?.user ? setTipModalOpen(true) : openAuth();

    const actions = { 
        openAuth, 
        openDeposit, 
        openVip: () => setVipModalOpen(true), 
        openCrates,
        openVault,
        openTip,
        openChat: () => router.visit('/contact') 
    };

    const handleSearchSubmit = (query) => {
        router.get('/', { search: query }, { preserveState: true });
    };

    return (
        <div className="nw-app min-h-screen text-gray-100 flex flex-col font-sans">
            
            <a href="#main-content" className="nw-skip-link">Skip to content</a>
            
            {/* Flash Messages Toast */}
            {flash?.success && (
                <div className="fixed top-20 right-6 z-50 p-4 rounded-2xl bg-emerald-900/90 border border-emerald-500/50 text-emerald-200 text-xs font-bold shadow-2xl backdrop-blur-md animate-in slide-in-from-right duration-300">
                    ✨ {flash.success}
                </div>
            )}
            {flash?.error && (
                <div className="fixed top-20 right-6 z-50 p-4 rounded-2xl bg-red-900/90 border border-red-500/50 text-red-200 text-xs font-bold shadow-2xl backdrop-blur-md animate-in slide-in-from-right duration-300">
                    ⚠️ {flash.error}
                </div>
            )}

            {/* Left Sidebar */}
            <Sidebar 
                isOpen={sidebarOpen} 
                setIsOpen={setSidebarOpen}
                onOpenDeposit={openDeposit}
                onOpenVip={() => setVipModalOpen(true)}
                onOpenCrates={openCrates}
                onOpenVault={openVault}
                onOpenTip={openTip}
            />

            {/* Backdrop for mobile sidebar */}
            {sidebarOpen && (
                <div 
                    onClick={() => setSidebarOpen(false)}
                    className="fixed inset-0 z-30 bg-black/60 backdrop-blur-sm lg:hidden"
                />
            )}

            {/* Main Application Area */}
            <div className="nw-main-shell flex flex-col flex-1 min-w-0">
                
                {/* Header */}
                <Header 
                    onToggleSidebar={() => setSidebarOpen(!sidebarOpen)}
                    onOpenAuth={openAuth}
                    onOpenDeposit={openDeposit}
                    onOpenVip={() => setVipModalOpen(true)}
                    onOpenCrates={openCrates}
                    onOpenVault={openVault}
                    onOpenTip={openTip}
                    searchQuery={searchQuery}
                    setSearchQuery={setSearchQuery}
                    onSearchSubmit={handleSearchSubmit}
                />

                {/* Main Page Content */}
                <main id="main-content" className="nw-main">
                    {typeof children === 'function' ? children(actions) : children}
                </main>

                {/* Footer */}
                <Footer />
            </div>

            {/* Modals */}
            {authModalOpen && <AuthModal
                initialMode={authMode}
                isOpen={authModalOpen} 
                onClose={() => setAuthModalOpen(false)}
            />}

            <DepositModal 
                isOpen={depositModalOpen} 
                onClose={() => setDepositModalOpen(false)} 
            />

            <VipModal 
                isOpen={vipModalOpen} 
                onClose={() => setVipModalOpen(false)} 
            />

            <CratesModal
                isOpen={cratesModalOpen}
                onClose={() => setCratesModalOpen(false)}
            />

            <VaultModal
                isOpen={vaultModalOpen}
                onClose={() => setVaultModalOpen(false)}
            />

            <TipModal
                isOpen={tipModalOpen}
                onClose={() => setTipModalOpen(false)}
            />

        </div>
    );
}
