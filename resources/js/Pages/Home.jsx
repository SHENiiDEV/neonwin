import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Radio, Rocket, Fish, Sparkles, ArrowRight, ShieldCheck, Gift, X } from 'lucide-react';
import MainLayout from '../Layouts/MainLayout';
import HeroSlider from '../Components/HeroSlider';
import CategoryPills from '../Components/CategoryPills';
import GameGrid from '../Components/GameGrid';
import FeatureBanners from '../Components/FeatureBanners';
import TournamentsBanner from '../Components/TournamentsBanner';
import HighlightsTable from '../Components/HighlightsTable';
import ContactBanner from '../Components/ContactBanner';
import ProvidersBar from '../Components/ProvidersBar';
import ChallengesSection from '../Components/ChallengesSection';
import HallOfFame from '../Components/HallOfFame';

const categoryNames = { 
    all: 'All games', 
    slots: 'Slots', 
    live: 'Live casino', 
    crash: 'Crash & fast games', 
    fishing: 'Fish hunter', 
    popular: 'Popular games' 
};

export default function Home({ 
    providers = [], 
    popularGames = [], 
    liveGames = [], 
    crashGames = [], 
    fishingGames = [], 
    allGames = [], 
    tournaments = [], 
    highlights = [], 
    filters = {} 
}) {
    const [browseAll, setBrowseAll] = useState(false);
    const category = filters.category || 'all';
    const provider = filters.provider || 'all';
    const isFiltered = category !== 'all' || provider !== 'all' || Boolean(filters.search);

    const selectCategory = value => { 
        setBrowseAll(value === 'all'); 
        router.get('/', { category: value, provider: 'all' }, { preserveState: true, preserveScroll: true }); 
    };

    const selectProvider = value => 
        router.get('/', { category, provider: value, search: filters.search || '' }, { preserveState: true, preserveScroll: true });

    const explore = () => { 
        selectCategory('all'); 
        document.getElementById('popular-games')?.scrollIntoView({ behavior: 'smooth' }); 
    };

    const selectProviderAndScroll = value => { 
        selectProvider(value); 
        document.getElementById('popular-games')?.scrollIntoView({ behavior: 'smooth' }); 
    };

    return (
        <MainLayout highlights={highlights}>
            {({ openAuth, openDeposit, openVip }) => (
                <>
                    <Head title="Neonwin — Good times. Golden energy.">
                        <meta name="description" content="Discover your next favorite game at Neonwin. Explore slots, live casino, fast games, tournaments and the VIP club. Social play for adults 18+." />
                    </Head>

                    <div className="nw-welcome-row">
                        <span><Sparkles size={15} /> YOUR WORLD OF PLAY</span>
                        <a href="#highlights-section">Community highlights <ArrowRight size={13} /></a>
                    </div>

                    <HeroSlider onOpenAuth={openAuth} onOpenDeposit={openDeposit} onOpenVip={openVip} />

                    <div className="nw-benefits">
                        <span><Gift size={16} /> A little more to look forward to</span>
                        <span><ShieldCheck size={16} /> Play for fun. Play responsibly.</span>
                        <span><Sparkles size={16} /> Your favorites, all in one place</span>
                    </div>

                    <CategoryPills selectedCategory={category} onSelectCategory={selectCategory} />

                    {isFiltered && (
                        <div className="nw-filter-summary">
                            <span>
                                {filters.search 
                                    ? `Results for “${filters.search}”` 
                                    : `${categoryNames[category] || 'Games'}${provider !== 'all' ? ` · ${providers.find(item => item.code === provider)?.name || provider}` : ''}`}
                            </span>
                            <button onClick={() => { setBrowseAll(false); router.get('/', {}, { preserveState: true, preserveScroll: true }); }}>
                                Clear filters <X size={13} />
                            </button>
                        </div>
                    )}

                    <GameGrid 
                        id="popular-games" 
                        title={isFiltered ? (filters.search ? 'Your search results' : categoryNames[category] || 'Discover games') : browseAll ? 'Explore the casino' : 'Popular right now'} 
                        subtitle={isFiltered ? 'Find the game that fits your mood.' : 'The games everyone’s coming back for.'} 
                        games={isFiltered || browseAll ? allGames : popularGames} 
                        providers={providers} 
                        selectedProvider={provider} 
                        onSelectProvider={selectProvider} 
                        onViewAll={browseAll || isFiltered ? undefined : explore} 
                    />

                    {!isFiltered && (
                        <>
                            <FeatureBanners />
                            <ChallengesSection />
                            {liveGames.length > 0 && (
                                <GameGrid 
                                    id="live-games" 
                                    title="The live casino experience" 
                                    subtitle="Real dealers. A seat with your name on it." 
                                    icon={Radio} 
                                    games={liveGames.slice(0, 6)} 
                                    onViewAll={() => selectCategory('live')} 
                                />
                            )}
                            <HallOfFame />
                            <TournamentsBanner tournament={tournaments[0]} onExploreGames={explore} />
                            {crashGames.length > 0 && (
                                <GameGrid 
                                    id="crash-games" 
                                    title="Fast games. Big energy." 
                                    subtitle="Get straight to the action." 
                                    icon={Rocket} 
                                    games={crashGames.slice(0, 6)} 
                                    onViewAll={() => selectCategory('crash')} 
                                />
                            )}
                            {fishingGames.length > 0 && (
                                <GameGrid 
                                    id="fishing-games" 
                                    title="Dive into something different" 
                                    subtitle="Discover the fish hunter collection." 
                                    icon={Fish} 
                                    games={fishingGames.slice(0, 6)} 
                                    onViewAll={() => selectCategory('fishing')} 
                                />
                            )}
                            <HighlightsTable highlights={highlights} />
                        </>
                    )}

                    <ContactBanner />
                    <ProvidersBar providers={providers} onSelectProvider={selectProviderAndScroll} />
                </>
            )}
        </MainLayout>
    );
}
