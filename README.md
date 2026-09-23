# Neonwin — Cyberpunk Social Sweeps Casino

![Neonwin Casino](https://images.unsplash.com/photo-1518709268805-4e9042af9f23?w=1200&auto=format&fit=crop&q=80)

**Neonwin** is a modern, high-performance Social Sweeps Casino application built with **Laravel 12**, **Inertia.js v3**, **React**, and **Tailwind CSS**. It incorporates seamless aggregator integration via **NexusGGR** with an advanced **Virtual Currency Gateway & Denomination Model** ($1\text{ SC} = 100\,000\text{ Coins}$).

---

## 🚀 Key Features

* **Virtual Currency Gateway & Denomination**:
  * Real-time currency normalization for seamless NexusGGR provider webhooks (`/gold_api`).
  * In-lobby balances and wagers displayed in millions of virtual Coins ($1\text{ SC} = 100\,000\text{ Coins}$).
  * Idempotent transaction processing with duplicate prevention on `txn_id_v2`.
* **Cyber Crates (Mystery Loot Boxes)**:
  * CS:GO / Cyberpunk horizontal roulette reel strip with animated sound effects.
  * 5 Progression Tiers: *Bronze Recruit*, *Silver Shadow*, *Gold Ronin*, *Platinum Cyber*, *Mythic Overlord*.
* **Cyber Vault (Piggy Bank)**:
  * Emotional bankroll protection with 4-digit PIN security to lock away coins.
* **Player-to-Player Tipping**:
  * Instant peer-to-peer coin transfers with 3% platform commission.
* **Cyber Syndicate (Referral Network)**:
  * 500,000 Coins signup bonus for referrers with live tracking and rakeback sharing.
* **Elite VIP Club**:
  * 5 VIP levels with 0x wagering requirements on all cashback, weekly rakeback, and level-up rewards.
* **Multiplier Hunts & Live Bounties**:
  * Real-time slot target challenge engine with prize pools up to 25,000,000 Coins.
* **Email System via PrivateEmail (Namecheap)**:
  * Transactional & welcome emails configured with `info@neonwin.co.uk`.

---

## 🛠 Tech Stack

* **Backend**: PHP 8.4, Laravel 12
* **Frontend**: React 18, Inertia.js v3, Vite, Tailwind CSS, Lucide Icons
* **Database**: SQLite (local) / MySQL / PostgreSQL (production)
* **Audio**: Web Audio API Sound Synthesizer (Zero external dependencies)
* **Testing**: PHPUnit 11 (100% test coverage for all core features)

---

## 📦 Getting Started

### 1. Prerequisites
* PHP >= 8.2 with OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON
* Composer >= 2.0
* Node.js >= 18.0 and NPM

### 2. Installation

```bash
# Clone the repository
git clone https://github.com/SHENiiDEV/neonwin.git
cd neonwin

# Install PHP dependencies
composer install

# Install JS dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Run database migrations and seeders
php artisan migrate --seed

# Build frontend assets
npm run build
```

### 3. Running Locally

```bash
# Start development servers
php artisan serve --port=8888
npm run dev
```

---

## 🧪 Testing

```bash
php artisan test --compact
```

---

## 📧 Email Configuration (PrivateEmail / Namecheap)

Set the following variables in your `.env` file:

```ini
MAIL_MAILER=smtp
MAIL_HOST=mail.privateemail.com
MAIL_PORT=587
MAIL_ENCRYPTION=tls
MAIL_USERNAME=info@neonwin.co.uk
MAIL_PASSWORD=your_email_password
MAIL_FROM_ADDRESS=info@neonwin.co.uk
MAIL_FROM_NAME="Neonwin Casino"
```

---

## 📄 License

Proprietary. All rights reserved.
