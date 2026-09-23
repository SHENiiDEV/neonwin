<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $gameTitle }} - Social Casino Frame (SC)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.9.3/dist/confetti.browser.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;800;900&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <style>
        body {
            background: radial-gradient(circle at center, #1b1235 0%, #080711 100%);
            font-family: 'Montserrat', sans-serif;
            overflow: hidden;
            user-select: none;
        }
        .neon-glow {
            text-shadow: 0 0 15px rgba(245, 158, 11, 0.8), 0 0 30px rgba(168, 85, 247, 0.5);
        }
        .slot-box {
            background: linear-gradient(180deg, rgba(28, 22, 54, 0.9) 0%, rgba(13, 10, 27, 0.95) 100%);
            border: 2px solid rgba(168, 85, 247, 0.3);
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.2), inset 0 0 25px rgba(0, 0, 0, 0.8);
        }
        .reel-item {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
    </style>
</head>
<body class="h-screen flex flex-col justify-between text-white p-4">

    <!-- Top Bar Inside Iframe -->
    <div class="flex items-center justify-between bg-black/40 backdrop-blur-md px-6 py-3 rounded-2xl border border-white/10">
        <div class="flex items-center space-x-3">
            <div class="w-3 h-3 rounded-full bg-emerald-500 animate-pulse"></div>
            <div>
                <span class="text-xs text-purple-400 font-bold tracking-wider uppercase">{{ $provider }}</span>
                <h1 class="text-lg font-black tracking-wide text-white">{{ $gameTitle }}</h1>
            </div>
        </div>
        <div class="flex items-center space-x-6">
            <div class="text-right">
                <div class="text-[10px] text-gray-400 uppercase font-bold tracking-wider">SC Balance</div>
                <div class="text-xl font-bold text-yellow-400 tracking-wider flex items-center gap-1.5 font-mono">
                    <span id="display-balance">...</span>
                    <span class="text-xs bg-yellow-950 px-1.5 py-0.5 rounded border border-yellow-500/40 text-yellow-300 font-black">SC</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Center Game Playground / Slot Visualizer -->
    <div class="flex-1 flex flex-col items-center justify-center my-4">
        <div class="slot-box w-full max-w-4xl p-8 rounded-3xl relative overflow-hidden flex flex-col items-center">
            
            <!-- Floating Sparkles & Background Deco -->
            <div class="absolute -top-20 -left-20 w-60 h-60 bg-purple-600/20 rounded-full blur-3xl pointer-events-none"></div>
            <div class="absolute -bottom-20 -right-20 w-60 h-60 bg-amber-600/20 rounded-full blur-3xl pointer-events-none"></div>

            <!-- Game Banner / Header -->
            <div class="text-center mb-6 z-10">
                <div class="inline-block px-4 py-1 rounded-full bg-purple-900/60 border border-purple-500/30 text-xs font-semibold text-purple-300 mb-2">
                    ⚡ SOCIAL CASINO • RTP 96.50%
                </div>
                <div id="win-message" class="text-2xl font-black text-transparent bg-clip-text bg-gradient-to-r from-yellow-300 via-amber-300 to-purple-300 neon-glow min-h-[36px]">
                    READY TO SPIN
                </div>
            </div>

            <!-- 5-Reel Visual Grid -->
            <div class="grid grid-cols-5 gap-3 w-full max-w-2xl bg-black/60 p-4 rounded-2xl border border-purple-500/20 z-10">
                <div id="r1" class="reel-item h-28 bg-gradient-to-b from-purple-950/60 to-slate-900/80 rounded-xl flex items-center justify-center text-4xl border border-white/5 shadow-inner">👑</div>
                <div id="r2" class="reel-item h-28 bg-gradient-to-b from-purple-950/60 to-slate-900/80 rounded-xl flex items-center justify-center text-4xl border border-white/5 shadow-inner">💎</div>
                <div id="r3" class="reel-item h-28 bg-gradient-to-b from-purple-950/60 to-slate-900/80 rounded-xl flex items-center justify-center text-4xl border border-white/5 shadow-inner">⚡</div>
                <div id="r4" class="reel-item h-28 bg-gradient-to-b from-purple-950/60 to-slate-900/80 rounded-xl flex items-center justify-center text-4xl border border-white/5 shadow-inner">💰</div>
                <div id="r5" class="reel-item h-28 bg-gradient-to-b from-purple-950/60 to-slate-900/80 rounded-xl flex items-center justify-center text-4xl border border-white/5 shadow-inner">⭐</div>
            </div>

            <!-- Multiplier Indicator -->
            <div class="mt-6 flex items-center gap-2 text-sm text-gray-400 z-10 font-mono">
                <span>Last Payout:</span>
                <span id="last-win-amount" class="text-emerald-400 font-bold text-base">0.00 SC</span>
                <span class="text-gray-600">|</span>
                <span>Multiplier:</span>
                <span id="last-multiplier" class="text-yellow-400 font-bold text-base">0x</span>
            </div>
        </div>
    </div>

    <!-- Bottom Controls Panel -->
    <div class="bg-black/50 backdrop-blur-md px-8 py-4 rounded-2xl border border-white/10 flex items-center justify-between max-w-4xl mx-auto w-full">
        <!-- Bet Adjuster -->
        <div class="flex items-center space-x-3">
            <span class="text-xs text-gray-400 font-bold uppercase">BET</span>
            <div class="flex items-center bg-purple-950/80 border border-purple-500/40 rounded-xl px-2 py-1">
                <button onclick="adjustBet(-1)" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-lg font-bold text-purple-300 transition">−</button>
                <span id="bet-value" class="w-20 text-center font-bold text-yellow-400 font-mono text-base">5.00 SC</span>
                <button onclick="adjustBet(1)" class="w-8 h-8 rounded-lg bg-white/5 hover:bg-white/10 flex items-center justify-center text-lg font-bold text-purple-300 transition">+</button>
            </div>
            <button onclick="setBet(25)" class="px-3 py-1.5 rounded-lg bg-purple-900/40 hover:bg-purple-800/60 border border-purple-500/30 text-xs font-semibold text-purple-300 transition">MAX</button>
        </div>

        <!-- Spin Action Button -->
        <button id="spin-btn" onclick="executeSpin()" class="relative group px-10 py-3.5 rounded-2xl bg-gradient-to-r from-yellow-500 via-amber-500 to-yellow-600 hover:from-yellow-400 hover:to-amber-400 font-black text-lg tracking-widest uppercase text-black shadow-neon-gold transition transform active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
            <span class="flex items-center gap-2">
                <span>⚡ SPIN</span>
            </span>
        </button>

        <!-- Quick Bet Presets -->
        <div class="flex items-center space-x-2">
            <button onclick="quickBet(1)" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-gray-300 border border-white/10 transition font-mono">1 SC</button>
            <button onclick="quickBet(5)" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-gray-300 border border-white/10 transition font-mono">5 SC</button>
            <button onclick="quickBet(10)" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-gray-300 border border-white/10 transition font-mono">10 SC</button>
            <button onclick="quickBet(50)" class="px-3 py-2 rounded-xl bg-white/5 hover:bg-white/10 text-xs font-bold text-gray-300 border border-white/10 transition font-mono">50 SC</button>
        </div>
    </div>

    <script>
        const userCode = "{{ $userCode }}";
        const gameCode = "{{ $gameCode }}";
        const providerCode = "{{ $provider }}";
        const gameCategory = "{{ $category }}";
        let currentBet = 5.00;
        let isSpinning = false;
        let userBalance = 0;

        const symbols = ['👑', '💎', '⚡', '💰', '⭐', '🔥', '🍇', '🍒', '7️⃣'];

        async function fetchLiveBalance() {
            try {
                const res = await fetch('/gold_api', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        method: 'user_balance',
                        user_code: userCode,
                        agent_secret: '5562754eb33e45937673a6bacdd8be9d'
                    })
                });
                const data = await res.json();
                if (data.status === 1) {
                    userBalance = data.user_balance;
                    document.getElementById('display-balance').innerText = userBalance.toFixed(2);
                }
            } catch(e) {
                console.error("Balance fetch error:", e);
            }
        }

        function adjustBet(delta) {
            currentBet = Math.max(1, currentBet + delta);
            document.getElementById('bet-value').innerText = currentBet.toFixed(2) + ' SC';
        }

        function setBet(amount) {
            currentBet = amount;
            document.getElementById('bet-value').innerText = currentBet.toFixed(2) + ' SC';
        }

        function quickBet(val) {
            currentBet = val;
            document.getElementById('bet-value').innerText = currentBet.toFixed(2) + ' SC';
        }

        async function executeSpin() {
            if (isSpinning) return;
            if (userBalance < currentBet) {
                document.getElementById('win-message').innerText = 'INSUFFICIENT SC FUNDS!';
                return;
            }

            isSpinning = true;
            const spinBtn = document.getElementById('spin-btn');
            spinBtn.disabled = true;
            document.getElementById('win-message').innerText = 'SPINNING...';

            // Reel Animation
            const reels = ['r1', 'r2', 'r3', 'r4', 'r5'];
            const interval = setInterval(() => {
                reels.forEach(id => {
                    const randomSym = symbols[Math.floor(Math.random() * symbols.length)];
                    document.getElementById(id).innerText = randomSym;
                });
            }, 80);

            // Generate outcome
            const isWin = Math.random() > 0.45;
            let multiplier = 0;
            if (isWin) {
                const multipliers = [0.5, 1.2, 2.0, 3.5, 5.0, 10.0, 25.0, 50.0];
                multiplier = multipliers[Math.floor(Math.random() * multipliers.length)];
            }
            const winAmount = +(currentBet * multiplier).toFixed(2);
            const roundId = Date.now();
            const txnId = '' + roundId;
            const txnIdV2 = 'txn_' + roundId + '_' + Math.random().toString(36).substring(2, 10);
            const gameType = gameCategory === 'live' ? 'live' : 'slot';

            setTimeout(async () => {
                clearInterval(interval);

                try {
                    // Send transaction structured according to official NexusGGR Social Casino doc
                    const payload = {
                        method: 'transaction',
                        agent_code: 'zenithplay',
                        agent_secret: '5562754eb33e45937673a6bacdd8be9d',
                        user_code: userCode,
                        game_type: gameType,
                        [gameType]: {
                            provider_code: providerCode,
                            game_code: gameCode,
                            type: 'BASE',
                            bet_money: currentBet,
                            win_money: winAmount,
                            round_id: roundId,
                            txn_id: txnId,
                            txn_id_v2: txnIdV2,
                            txn_type: 'debit_credit'
                        }
                    };

                    const res = await fetch('/gold_api', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify(payload)
                    });

                    const data = await res.json();
                    if (data.status === 1) {
                        userBalance = data.user_balance;
                        document.getElementById('display-balance').innerText = userBalance.toFixed(2);

                        if (window.parent && window.parent !== window) {
                            window.parent.postMessage({
                                type: 'NEONWIN_BALANCE_UPDATE',
                                balance: userBalance,
                                win: winAmount
                            }, '*');
                        }
                    }
                } catch(e) {
                    console.error("Tx error:", e);
                }

                // Finalize visuals
                if (winAmount > 0) {
                    const winningSymbol = symbols[Math.floor(Math.random() * 4)];
                    document.getElementById('r1').innerText = winningSymbol;
                    document.getElementById('r2').innerText = winningSymbol;
                    document.getElementById('r3').innerText = winningSymbol;

                    document.getElementById('win-message').innerText = multiplier >= 5 ? '🔥 BIG WIN! ' + winAmount.toFixed(2) + ' SC' : '✨ WIN! ' + winAmount.toFixed(2) + ' SC';
                    document.getElementById('last-win-amount').innerText = winAmount.toFixed(2) + ' SC';
                    document.getElementById('last-multiplier').innerText = multiplier + 'x';

                    if (multiplier >= 5 && window.confetti) {
                        confetti({
                            particleCount: 100,
                            spread: 70,
                            origin: { y: 0.6 }
                        });
                    }
                } else {
                    document.getElementById('win-message').innerText = 'TRY AGAIN';
                    document.getElementById('last-win-amount').innerText = '0.00 SC';
                    document.getElementById('last-multiplier').innerText = '0x';
                }

                isSpinning = false;
                spinBtn.disabled = false;
            }, 1200);
        }

        // Init
        fetchLiveBalance();
    </script>
</body>
</html>
