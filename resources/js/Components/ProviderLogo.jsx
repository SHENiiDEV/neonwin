import React, { useState } from 'react';

// Flexrix's catalogue does not include logo URLs; use each studio's own website icon.
const studioDomains = {
    '3oaks': '3oaks.com',
    amatic: 'amatic.com',
    amigo: 'amigogaming.com',
    amusnet: 'amusnet.com',
    amusnetlive: 'amusnet.com',
    aviatrix: 'aviatrix.bet',
    betsoft: 'betsoft.com',
    bgaming: 'bgaming.com',
    cq9: 'cq9gaming.com',
    endorphina: 'endorphina.com',
    evolution: 'evolution.com',
    egt: 'egt.com',
    fugaso: 'fugaso.com',
    greentube: 'greentube.com',
    habanero: 'habanerosystems.com',
    hacksaw: 'hacksawgaming.com',
    kajot: 'kajotgames.com',
    merkur: 'merkur-gaming.com',
    microgaming: 'microgaming.io',
    netent: 'netent.com',
    pgsoft: 'pgsoft.com',
    platipus: 'platipusgaming.com',
    playngo: 'playngo.com',
    popok: 'popokgaming.com',
    pragmaticplay: 'pragmaticplay.com',
    pragmaticplaylivepro: 'pragmaticplay.com',
    redtiger: 'redtiger.com',
    religa: 'religa.com',
    relaxgaming: 'relax-gaming.com',
    rubyplay: 'rubyplay.com',
    smartsoft: 'smartsoftgaming.com',
    spinomenal: 'spinomenal.com',
    spinmatic: 'spinmatic.com',
    spadegaming: 'spadegaming.com',
    spribe: 'spribe.co',
    wazdan: 'wazdan.com',
    yggdrasil: 'yggdrasilgaming.com',
};

function studioKey(value) {
    return String(value || '').toLowerCase().replace(/[^a-z0-9]/g, '');
}

export default function ProviderLogo({ provider }) {
    const [failed, setFailed] = useState(false);
    const name = provider.name || provider.external_code || provider.code;
    const domain = studioDomains[studioKey(name)] || studioDomains[studioKey(provider.external_code)];
    const source = provider.logo_url || (domain ? `https://www.google.com/s2/favicons?domain=${domain}&sz=128` : null);

    if (!source || failed) {
        return <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/5 text-sm font-black text-purple-200" aria-hidden="true">{name.charAt(0).toUpperCase()}</span>;
    }

    return <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/10 bg-white/10 p-1.5">
        <img src={source} alt="" loading="lazy" referrerPolicy="no-referrer" onError={() => setFailed(true)} className="max-h-full max-w-full object-contain" />
    </span>;
}
