import React from 'react';
import LegalPage from '../../Components/LegalPage';

const sections = [
    ['Eligibility and your account', [
        'Neonwin is for adults aged 18 or above who are eligible to use the service where they are located. Do not register or play on behalf of another person. Give accurate registration and profile details, keep them up to date, and use only your own account. We may ask for evidence of age or identity before allowing access to a feature.',
        'Keep your password, device and vault PIN private. Do not share an account or allow a minor to use it. Contact support promptly if you suspect unauthorized access. Creating additional accounts to claim the same promotion or evade an account restriction is prohibited.',
    ]],
    ['EUR wallet and game settlement', [
        'The primary wallet is denominated in EUR. Previous SC balances are archived and have not been converted to EUR. Your playable balance changes when an accepted wallet transaction is recorded. A game animation or displayed result alone does not confirm a financial settlement.',
        'Game rules, permitted stakes and availability may differ by provider, game and location. Check the rules shown with a game before playing. If a bet, win, refund or rollback appears incorrect, stop playing and send support the game name, approximate time and transaction reference if available. We will review the wallet and provider records and correct a verified processing error.',
    ]],
    ['Deposits and withdrawals', [
        'Payment processing is currently unavailable. Do not send money outside the cashier or in response to an unsolicited message. Once a provider is enabled, the cashier must show the amount, currency and applicable conditions before you authorize payment. A payment is credited only after its successful status is verified.',
        'Withdrawals are not currently available. Both identity documents must be approved before the withdrawal entry is shown; approval does not guarantee or activate a payout. Any future withdrawal process, limits, fees and verification requirements must be presented before requests can be made. We do not currently promise payout times or payment methods.',
    ]],
    ['Promotions, FreeSpins and VIP rewards', [
        'Each offer has its own eligibility, percentage, minimum deposit, cap, availability dates and usage limits. Check the displayed offer terms before participating. A registration code reserves the offer associated with that code; it is not an immediate cash credit.',
        'Only the first confirmed deposit qualifies for a first-deposit bonus. Where eligible deposit offers overlap, the applicable offer with the highest calculated bonus is applied; deposit bonuses do not stack. A deposit below an offer’s minimum does not qualify. FreeSpins require provider confirmation; reserved spins are not credited to the cash wallet.',
        'Daily wheel and VIP rewards are subject to the eligibility, timing and funding conditions displayed for them. A displayed potential prize or future rakeback rate is not a credited balance. Where a reward calculation or settlement is corrected, the related wallet entry may also be corrected with an audit record.',
    ]],
    ['Cyber Vault', [
        'Cyber Vault moves EUR between your playable balance and your vault balance inside the same account. It is not a bank account, investment, deposit limit or external withdrawal. A PIN, when set, is required to return funds to the playable wallet.',
    ]],
    ['Verification and account protection', [
        'Identity verification requires an identity document and a selfie. Replacing either file begins a new review. We may request clarification where information is incomplete or inconsistent. Never submit someone else’s documents.',
        'We may temporarily restrict access to protect an account, investigate suspected fraud or resolve a transaction discrepancy. Contact support if a restriction appears to be a mistake. A restriction does not itself determine the ownership of a wallet balance.',
    ]],
    ['Fair use and availability', [
        'Do not use automated tools, exploit a technical error, manipulate game transactions or interfere with other players or the service. Do not attempt to reverse an accepted transaction by submitting it again. Verified mistakes, including duplicate credits, may be corrected against the account ledger.',
        'Games, sportsbook features and account tools may be unavailable during maintenance or provider interruptions. Do not repeatedly submit a payment or uncertain financial request. No game, promotion or personal return statistic guarantees future winnings.',
    ]],
    ['Privacy, messages and complaints', [
        'Our Privacy policy explains how account, game and verification information is used. Marketing emails are optional and require a separate opt-in. Account email and messenger contact choices can be managed in Profile → Preferences. Disabling marketing messages does not change an accepted game transaction or wallet record.',
        'Use the contact page for account questions, balance discrepancies and complaints. Include the relevant reference and time so the records can be reviewed. Individual promotion conditions remain attached to each offer; changes to a reserved promotion do not replace its saved conditions. Material changes to these platform terms should be brought to players’ attention before they take effect.',
    ]],
];

export default function Terms() {
    return <LegalPage title="Terms & conditions" description="The account, wallet, games and promotions rules for the EUR platform." path="/terms" sections={sections} />;
}
