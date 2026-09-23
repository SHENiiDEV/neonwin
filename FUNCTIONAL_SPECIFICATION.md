# 🎰 Social Sweeps Casino — Полная функциональная спецификация и архитектурный чертёж (Blueprint)

Этот документ содержит полное техническое и функциональное описание платформы **Social Sweeps Casino**. Он разработан как готовая пошаговая спецификация, позволяющая быстро воссоздать 100% аналогичный функционал на любом новом проекте с любым графическим дизайном или брендингом.

---

## 📑 Содержание

1. [Архитектурный стек и инфраструктура](#1-архитектурный-стек-и-инфраструктура)
2. [Аутентификация, регистрация и комплаенс](#2-аутентификация-регистрация-и-комплаенс)
3. [Экономика монет (Sweeps Coins / SC) и пополнение (Top-Up)](#3-экономика-монет-sweeps-coins--sc-и-пополнение-top-up)
4. [Интеграция с игровым провайдером (NexusGGR / Seamless API)](#4-интеграция-с-игровым-провайдером-nexusggr--seamless-api)
5. [Каталог игр, лобби, поиск и фильтры](#5-каталог-игр-лобби-поиск-и-фильтры)
6. [Cyber Crates (Лутбоксы с рулеткой открытия)](#6-cyber-crates-лутбоксы-с-рулеткой-открытия)
7. [Cyber Vault / Сейф игрока (Piggy Bank)](#7-cyber-vault--сейф-игрока-piggy-bank)
8. [Cyber Syndicate (Реферальная программа / Refer-a-Friend)](#8-cyber-syndicate-реферальная-программа--refer-a-friend)
9. [Player-to-Player Tipping (P2P Чаевые)](#9-player-to-player-tipping-p2p-чаевые)
10. [VIP-клуб и программа лояльности](#10-vip-клуб-и-программа-лояльности)
11. [Multiplier Hunt (Охота за иксами) и Hall of Fame](#11-multiplier-hunt-охота-за-иксами-и-hall-of-fame)
12. [Синтезатор звука (Web Audio API Sound Engine)](#12-синтезатор-звука-web-audio-api-sound-engine)
13. [Профиль игрока и статистика](#13-профиль-игрока-и-статистика)
14. [Юридический блок и страницы комплаенса](#14-юридический-блок-и-страницы-комплаенса)
15. [Схема базы данных и миграций](#15-схема-базы-данных-и-миграций)
16. [Чек-лист автоматизированного тестирования (PHPUnit)](#16-чек-лист-автоматизированного-тестирования-phpunit)

---

## 1. Архитектурный стек и инфраструктура

- **Backend Framework**: Laravel 12+ (PHP 8.4+)
- **Frontend SPA Layer**: Inertia.js v3 + React 19 (без необходимости создания отдельного REST/GraphQL API для страниц)
- **Styling**: Tailwind CSS + кастомная дизайн-система (токены, неоновые эффекты, glassmorphism)
- **Icons**: Lucide React
- **HTTP Client**: Axios / Inertia router
- **Audio Engine**: Pure Web Audio API (генерация синусоидальных, треугольных и пилообразных волн в браузере без тяжелых MP3-файлов)
- **Database**: MySQL 8.0+ / PostgreSQL / SQLite (для тестов)
- **Кэширование и очереди**: Redis / Database Queue

---

## 2. Аутентификация, регистрация и комплаенс

### 2.1. Форма регистрации (Поля)
В соответствии с требованиями платежных шлюзов и регуляторов Social Casino:
1. **Name** (Имя) — обязательное, строка до 100 символов.
2. **Surname** (Фамилия) — обязательное, строка до 100 символов.
3. **Email** — уникальный email, валидация формата.
4. **Password** — минимум 6–8 символов.
5. **Phone number** — международный формат (например, `+1...`, `+44...`, `+357...`).
6. **Date of birth** (Дата рождения) — обязательное поле. Серверная валидация: возраст **строго 18+** (`before_or_equal:today - 18 years`).
7. **Address (Разделен на 4 блока)**:
   - *Street, house number, apartment...* (Улица, дом, кв.)
   - *City* (Город)
   - *Country* (Страна) — выпадающий список стран мира за исключением запрещенных юрисдикций.
   - *Post code* (Почтовый индекс)
8. **Чекбокс согласия**:
   - Текст: *«I agree to the Terms & Conditions and Privacy Policy»* с кликабельными ссылками на соответствующие модальные окна или страницы `/terms` и `/privacy`.
9. **Referral Code (Опционально)**:
   - Автоматически считывается из URL параметра `?ref=CODE` и подставляется в скрытое или видимое поле формы.

### 2.2. Список запрещенных стран (Restricted Jurisdictions)
Серверная валидация блокирует регистрацию для стран:
> `Sudan`, `Dem. Rep. of the Congo`, `Iran`, `Mali`, `Myanmar (Burma)`, `North Korea`, `South Sudan`, `Syria`, `Yemen`, `Afghanistan`, `Belarus`, `Central African Republic`, `Cuba`, `Haiti`, `Iraq`, `Russia`, `Somalia`, `Venezuela`, `Zimbabwe`.

### 2.3. Логика бонуса за регистрацию
- **Welcome Bonus**: 0 SC на баланс (без начисления бесплатных денег во избежание абуза мультиаккаунтов).
- **Welcome Recruit Crate**: Новичку автоматически начисляется 1 бронзовый кибер-кейс (`Cyber Recruit Welcome Crate`) со статусом `pending`.
- **Referral Bounty**: Если указан реферальный код, пригласившему игроку начисляется **5.00 SC** на баланс + запись в `referral_earnings`.

### 2.4. Быстрый вход (Demo Login)
- Кнопка *«Quick Demo Access»* для мгновенного входа тестовым пользователем (`player@neonwin.com`) с предустановленным балансом и VIP-уровнем для быстрого тестирования фич.

---

## 3. Экономика монет (Sweeps Coins / SC) и пополнение (Top-Up)

### 3.1. Валюты пополнения
- Доступны только фиатные валюты: **EUR (€)**, **USD ($)**, **GBP (£)**.
- Криптовалютные методы скрыты/отключены.

### 3.2. Пакеты монет (Coin Packs)
Минимальный чек — 50 EUR. Соотношение покупки:
| Базовая цена (EUR) | Базовые SC | Бонусные SC | Итого SC | Название пака |
|:---|:---|:---|:---|:---|
| **€50** | 25 SC | +3 SC | **28 SC** | Golden Energy (Популярный) |
| **€100** | 50 SC | +8 SC | **58 SC** | Extra Bright |
| **€250** | 125 SC | +25 SC | **150 SC** | High Roller |
| **€500** | 250 SC | +60 SC | **310 SC** | The Full Glow |
| **€1000** | 500 SC | +150 SC | **650 SC** | Neon Emperor |
| **€2500** | 1250 SC | +450 SC | **1700 SC** | Cyber Whale |

### 3.3. Daily Free Bonus (Ежедневный бесплатный бонус 1 SC)
- **Размер**: 1.00 SC.
- **Интервал**: строго каждые **24 часа**.
- **Механизм защиты**: 
  - На сервере сохраняется метка `last_daily_bonus_at`.
  - При попытке повторного запроса до истечения 24 часов сервер возвращает ошибку `422` с точным временем оставшейся блокировки.
  - На клиенте отображается живой таймер обратного отсчета: `XXh XXm XXs` и неактивная кнопка.

---

## 4. Интеграция с игровым провайдером (NexusGGR / Seamless API) и Шлюз деноминации (Virtual Currency Gateway)

Платформа использует модель **Seamless Wallet**: игровой провайдер NexusGGR обращается к нашему API по защищенным вебхукам в реальном времени.

### 4.1. Запуск игры (Game Launch)
- **URL**: `/game/{slug}`
- **Параметры**:
  - `demo=1` — режим бесплатной демо-игры (кредиты провайдера).
  - Без параметра — запуск на реальные Sweeps Coins / виртуальные монеты авторизованного игрока.
- **Запрос к NexusGGR API**:
  - Метод: `POST /api/v1/games/launch`
  - Body: `user_id`, `game_code`, `currency: "SC"`, `return_url`, `session_ip`.
  - Ответ: `launch_url` для рендера в безопасном `<iframe>`.

---

### 4.2. Архитектура деноминации (Virtual Currency Gateway)

#### 🎯 Суть проблемы и решение
В Social Casino игроки оперируют сотнями тысяч и миллионами монет (например, 100 000 монет за регистрацию, ставка 10 000 монет на спин). Однако слоты провайдеров (Pragmatic Play, NetEnt, Hacksaw) и Seamless API NexusGGR работают в стандартном 2-значном десятичном формате кредитов (например, 10.00 кредитов, ставка 0.10, выигрыш 2.00).

Для решения этой задачи создается прослойка-конвертер (**Virtual Currency Gateway**), которая на лету нормализует балансы и транзакции между базой данных Social Casino и API провайдера.

#### 📐 1. Математика деноминации
Выбирается фиксированный коэффициент масштабирования (**`RATE`**):

$$\text{Provider API Balance} = \frac{\text{Virtual Coins}}{\text{RATE}}$$

**Пример с коэффициентом $RATE = 100\,000$**:
- **Игрок видит в лобби**: $1\,000\,000$ монет.
- **NexusGGR получает баланс**: $1\,000\,000 / 100\,000 = \mathbf{10.00\text{ кредитов}}$.
- **Игрок делает спин со ставкой**: $5\,000$ монет.
- **NexusGGR присылает debit на**: $0.05$ кредита.
- **Игрок выигрывает в слоте $\times 20$**: NexusGGR шлёт credit на $1.00$ кредит.
- **Бэкенд начисляет игроку**: $1.00 \times 100\,000 = \mathbf{100\,000\text{ монет}}$.

#### ⚖️ 2. Правило строгого округления
- Внутренний баланс пользователя в базе данных хранится в целых числах (`BIGINT UNSIGNED`) или фиксированных десятичных значениях без потери точности.
- При отдаче баланса в сторону NexusGGR значение **всегда округляется вниз (floor)** до 2 знаков после запятой, чтобы провайдер никогда не отклонял запросы из-за лишних долей цента:

$$\text{Provider Amount} = \frac{\lfloor (\text{Virtual Coins} / \text{RATE}) \times 100 \rfloor}{100}$$

---

### 4.3. Схема архитектуры перехвата и вебхуков

```
┌────────────────────────────────────────────────────────┐
│             Игровой слот (IFrame / Nexus)              │
└───────────────────────────┬────────────────────────────┘
                            │
                            │ 1. HTTP Callback (/balance, /bet, /win) в кредитах
                            ▼
┌────────────────────────────────────────────────────────┐
│     Seamless Gateway / NexusCallbackController         │
├────────────────────────────────────────────────────────┤
│ ├── Получил Bet: 0.10 кредитов                         │
│ ├── Перевел в монеты: 0.10 * 100_000 = 10 000 монет    │
│ ├── Блокировка строки игрока: lockForUpdate()          │
│ ├── Списание 10 000 монет из БД + начисление VIP XP    │
│ ├── Проверка на дубликат: provider_tx_id               │
│ └── Ответ провайдеру: актуальный остаток / 100_000     │
└────────────────────────────────────────────────────────┘
```

#### 💻 3. Реализация Webhook-контроллера (Single Transaction & Idempotency)

```php
<?php

namespace App\Http\Controllers;

use App\Models\GameTransaction;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NexusCallbackController extends Controller
{
    // 100 000 виртуальных монет = 1.00 кредит у NexusGGR
    public const DENOMINATION_RATE = 100000;

    /**
     * 1. Возврат баланса провайдеру (в кредитах провайдера)
     * POST /api/seamless/balance
     */
    public function getBalance(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required'],
        ]);

        $user = User::findOrFail($validated['user_id']);

        // Конвертируем виртуальные монеты в кредиты провайдера с floor до 2 знаков
        $providerBalance = floor(($user->game_balance / self::DENOMINATION_RATE) * 100) / 100;

        return response()->json([
            'status' => 'OK',
            'balance' => number_format($providerBalance, 2, '.', ''),
        ]);
    }

    /**
     * 2. Обработка ставки и выигрыша (Single Transaction / Debit & Credit)
     * POST /api/seamless/callback (или /bet, /win)
     */
    public function handleCallback(Request $request): JsonResponse
    {
        $userId        = $request->input('user_id');
        $transactionId = $request->input('transaction_id');
        $betAmount     = (float) $request->input('bet', 0.00); // в кредитах провайдера
        $winAmount     = (float) $request->input('win', 0.00); // в кредитах провайдера

        // Переводим кредиты NexusGGR в виртуальные монеты Social Casino
        $coinsToDebit  = (int) round($betAmount * self::DENOMINATION_RATE);
        $coinsToCredit = (int) round($winAmount * self::DENOMINATION_RATE);

        return DB::transaction(function () use ($userId, $transactionId, $coinsToDebit, $coinsToCredit, $betAmount, $winAmount) {
            // Защита от дублей (идемпотентность)
            $existingTx = GameTransaction::where('provider_tx_id', $transactionId)->first();
            if ($existingTx) {
                return response()->json([
                    'status' => 'OK', 
                    'balance' => number_format($existingTx->response_balance, 2, '.', '')
                ]);
            }

            // Блокируем запись пользователя на время расчета (защита от Race Conditions)
            $user = User::where('id', $userId)->lockForUpdate()->firstOrFail();

            if ($user->game_balance < $coinsToDebit) {
                return response()->json([
                    'status' => 'FAIL',
                    'message' => 'INSUFFICIENT_FUNDS'
                ], 400);
            }

            // Корректируем виртуальный баланс
            $user->game_balance = $user->game_balance - $coinsToDebit + $coinsToCredit;
            
            // Автоматический учет вейджера и начисление VIP XP (1 SC = 1 XP)
            if ($coinsToDebit > 0) {
                $user->awardVipXp($coinsToDebit / self::DENOMINATION_RATE);
            }
            
            $user->save();

            $newProviderBalance = floor(($user->game_balance / self::DENOMINATION_RATE) * 100) / 100;

            // Логируем транзакцию
            GameTransaction::create([
                'user_id'          => $user->id,
                'provider_tx_id'   => $transactionId,
                'coins_debit'      => $coinsToDebit,
                'coins_credit'     => $coinsToCredit,
                'response_balance' => $newProviderBalance,
            ]);

            return response()->json([
                'status'  => 'OK',
                'balance' => number_format($newProviderBalance, 2, '.', ''),
            ]);
        });
    }

    /**
     * 3. Обработка отмены раунда (Rollback / Refund)
     * POST /api/seamless/rollback
     */
    public function handleRollback(Request $request): JsonResponse
    {
        $transactionId = $request->input('transaction_id');
        $referenceTxId = $request->input('reference_transaction_id');

        return DB::transaction(function () use ($transactionId, $referenceTxId) {
            $existingRollback = GameTransaction::where('provider_tx_id', $transactionId)->first();
            if ($existingRollback) {
                return response()->json(['status' => 'OK', 'balance' => number_format($existingRollback->response_balance, 2, '.', '')]);
            }

            $originalTx = GameTransaction::where('provider_tx_id', $referenceTxId)->firstOrFail();
            $user = User::where('id', $originalTx->user_id)->lockForUpdate()->firstOrFail();

            // Возвращаем списанный бет и забираем начисленный выигрыш
            $user->game_balance = $user->game_balance + $originalTx->coins_debit - $originalTx->coins_credit;
            $user->save();

            $newProviderBalance = floor(($user->game_balance / self::DENOMINATION_RATE) * 100) / 100;

            GameTransaction::create([
                'user_id'          => $user->id,
                'provider_tx_id'   => $transactionId,
                'coins_debit'      => -$originalTx->coins_debit,
                'coins_credit'     => -$originalTx->coins_credit,
                'response_balance' => $newProviderBalance,
            ]);

            return response()->json([
                'status' => 'OK',
                'balance' => number_format($newProviderBalance, 2, '.', ''),
            ]);
        });
    }
}
```

---

### 4.4. Критические нюансы настройки деноминации

1. **Минимальная ставка в слотах (Min Bet Alignment)**:
   - Убедитесь, что после деления ставка не становится меньше минимально допустимой у провайдера.
   - Обычно у NexusGGR минимальный bet в играх Pragmatic составляет **0.10 или 0.20 кредита**.
   - При $RATE = 100\,000$, минимальная ставка в интерфейсе Social Casino должна составлять не менее **10 000 – 20 000 монет**.

2. **Отображение баланса поверх IFrame (Overlay Bar)**:
   - Внутри iframe оригинального слота провайдера баланс отображается в кредитах (например, `10.00 CREDITS` или `10.00 USD`).
   - Для сохранения погружения поверх iframe размещается фирменная верхняя панель Social Casino (`GamePlayer.jsx`) с отображением реального баланса в миллионах монет, кнопками быстрого пополнения, выхода в лобби и полноэкранного режима.
   - Если провайдер поддерживает флаг конфигурации `currency_display=coins` в URL запуска, его необходимо передавать при инициализации сессии.

---

## 5. Каталог игр, лобби, поиск и фильтры

### 5.1. Категории игр (Category Pills)
- **All Games** (Все игры)
- **Slots** (Слоты)
- **Live Casino** (Живое казино: Рулетка, Блэкджек, Баккара)
- **Crash & Fast Games** (Краш-игры: Aviator, Spaceman, Mines)
- **Fish Hunter** (Рыбалка / Аркады)
- **Popular Games** (Топ игр)

### 5.2. Панель провайдеров (Providers Bar)
- Pragmatic Play, Evolution, Hacksaw Gaming, Nolimit City, PG Soft, Push Gaming, Spribe, NetEnt, Relax Gaming.
- Фильтрация по клику с сохранением активной категории и скроллом к сетке.

### 5.3. Живой поиск (Live Autocomplete Search)
- Поиск на лету с дебаунсом 250мс по названию игры и провайдеру.
- Выпадающее окно превью с постером, RTP%, провайдером и прямым переходом.
- Поддержка горячих клавиш: `Enter` — переход ко всем результатам в лобби, `Esc` — закрытие.

### 5.4. Избранное (Favorites ❤️) и Недавно сыгранные (Recently Played)
- Кнопка «сердечко» на карточке каждой игры сохраняет игру в `user_favorites`.
- При запуске игры автоматически фиксируется запись в `user_recent_games`.
- Доступны фильтры быстрого доступа в лобби и профиле.

---

## 6. Cyber Crates (Лутбоксы с рулеткой открытия)

### 6.1. Правила начисления кейсов
- При регистрации по реферальной ссылке ➔ **Bronze Recruit Crate**.
- За каждые 100 SC вейджера ➔ **Silver Runner Crate**.
- При каждом повышении VIP-уровня ➔ **Gold / Platinum / Mythic Crate**.

### 6.2. Тиры кейсов и диапазоны призов
| Тир | Название | Диапазон SC | Макс. SC | VIP XP | Вероятные дропы SC |
|:---|:---|:---|:---|:---|:---|
| 🥉 **Bronze** | Cyber Recruit | 2 – 10 SC | **10 SC** | 50 – 250 XP | 2, 3, 5, 10 SC |
| 🥈 **Silver** | Cyber Runner | 5 – 20 SC | **20 SC** | 100 – 500 XP | 5, 8, 12, 20 SC |
| 🥇 **Gold** | Ronin Gold | 10 – 50 SC | **50 SC** | 250 – 1000 XP | 10, 15, 25, 50 SC |
| 💠 **Platinum** | Neon Master | 20 – 100 SC | **100 SC** | 500 – 2000 XP | 20, 35, 50, 100 SC |
| 💎 **Mythic** | Cyber Diamond | 50 – 500 SC | **500 SC** | 1000 – 5000 XP | 50, 75, 100, 250, 500 SC |

### 6.3. Интерактивная рулетка открытия (Roulette Reel)
- **Лента ячеек**: генерируется массив из 40 ячеек с различными призами и тирами.
- **Выигрышный слот**: сервер определяет награду, которая помещается на целевую позицию (индекс 30).
- **Физика движения**: лента стартует с нулевой позиции и за 4.5 секунды плавно прокручивается через центральный лазерный прицел (`▼` и `▲`) с плавной деселерацией `cubic-bezier(0.12, 0.85, 0.22, 1)`.
- **Звуковое сопровождение**: программный аудио-тик (`playReelTickSound`) звучит на каждой пролетающей ячейке с замедлением частоты тиков.
- **Победная вспышка**: остановка строго в центре ячейки ➔ золотой контурный импульс ➔ триумфальный фанфар `playEpicFanfareSound` ➔ окно награды.

---

## 7. Cyber Vault / Сейф игрока (Piggy Bank)

Инструмент ответственной игры и защиты банкролла:

### 7.1. Функционал депозита в сейф
- Игрок может моментально переместить любую сумму SC с основного игрового баланса в сейф.
- Быстрые кнопки: **25%**, **50%**, **100%** баланса.
- Средства в сейфе изолированы от ставок в играх.

### 7.2. Функционал вывода из сейфа и защита PIN-кодом
- Вывод средств из сейфа обратно на игровой баланс.
- **Опциональный 4-значный PIN-код**:
  - Игрок может установить персональный PIN.
  - При наличии установленного PIN-кода снятие средств из сейфа невозможно без ввода корректного PIN.
  - Позволяет защитить банкролл от необдуманных импульсивных ставок.

---

## 8. Cyber Syndicate (Реферальная программа / Refer-a-Friend)

### 8.1. Идентификация и промо-материалы
- У каждого пользователя уникальный реферальный код: `NW-REF-XXXXXX`.
- Персональная реферальная ссылка: `https://your-domain.com/?ref=NW-REF-XXXXXX`.
- Встроенный генератор динамического **QR-кода** во вкладке Syndicate в профиле.
- Кнопка быстрого копирования ссылки в буфер обмена в один клик.

### 8.2. Награды синдиката
1. **Реферальный бонус за регистрацию**: **5.00 SC** мгновенно начисляются на баланс пригласившего после создания аккаунта рекрутом.
2. **Приветственный подарок рекруту**: бесплатный кейс `Cyber Recruit Crate`.
3. **Пожизненный процент рейкбека**: процент от ставок рекрутов поступает на счёт реферера.
4. **Таблица синдиката**: список приглашенных пользователей с их именами, кодами, VIP-уровнями и историей начислений.

---

## 9. Player-to-Player Tipping (P2P Чаевые)

### 9.1. Механика перевода
- Перевод Sweeps Coins между игроками по **User Code** (`ZP-XXXXXX` / `NW-XXXXXX`) или никнейму.
- Модальное окно `TipModal` с валидацией баланса отправителя и существования получателя.
- Возможность прикрепить текстовую записку (до 150 символов).

### 9.2. Комиссия платформы
- Автоматически удерживается фиксированная комиссия **3%**.
- Пример: Отправка 100 SC ➔ Комиссия платформы 3 SC ➔ Получатель получает 97 SC.
- Полная прозрачность: динамический расчет комиссии отображается в интерфейсе перед отправкой.
- Блокировка отправки чаевых самому себе.

---

## 10. VIP-клуб и программа лояльности

### 10.1. Уровни VIP-прогрессии
1. 🥉 **Bronze Recruit** (0 – 1,000 XP) — Базовый рейкбек 5%, ежедневные бонусы.
2. 🥈 **Silver Ronin** (1,000 – 5,000 XP) — Рейкбек 8%, еженедельный кэшбэк 5%, серебряный кейс.
3. 🥇 **Gold Shogun** (5,000 – 25,000 XP) — Рейкбек 12%, еженедельный кэшбэк 10%, золотой кейс, персональный менеджер.
4. 💠 **Cyber Lord Platinum** (25,000 – 100,000 XP) — Рейкбек 16%, кэшбэк 14%, платиновый кейс, приоритетный вывод.
5. 💎 **Cyber Diamond Mythic** (100,000+ XP) — Рейкбек 20%, кэшбэк 18%, мифический кейс 500 SC, эксклюзивные дропы.

### 10.2. Механика начисления XP
- $1 SC вейджера в играх = 1 VIP XP.
- Автоматический пересчет прогресс-бара до следующего ранга и всплывающее поздравление при level-up.

---

## 11. Multiplier Hunt (Охота за иксами) и Hall of Fame

### 11.1. Multiplier Hunt (Live Challenges)
- Список активных испытаний на главной странице (например: «Поймай 500x в Sweet Bonanza со ставкой от 0.50 SC»).
- Фиксированный призовой фонд (Bounty Pool, например 250 SC).
- Первый игрок, выбивший целевой множитель, забирает призовой фонд автоматически.

### 11.2. Hall of Fame (Зал славы дня)
- Виджет на главной странице с крупнейшими зафиксированными выигрышами дня:
  - Игрок, слот, множитель (например, `2,150x`), сумма выигрыша в SC, время события.

---

## 12. Синтезатор звука (Web Audio API Sound Engine)

Полностью автономный синтезатор звука на чистом JavaScript ([`soundEffects.js`](file:///Users/mihailssegins/neonwin/resources/js/utils/soundEffects.js)) без загрузки MP3/WAV файлов.

### 12.1. Доступные аудио-генераторы:
- `playClickSound()`: короткий щелчок интерфейса (синусоида 800Hz ➔ 400Hz).
- `playCoinSound()`: звон золотых монет (двухтональный аккорд B5 + E6 / B6).
- `playCrateUnlockSound()`: эффект лазерного раскручивания (пилообразная волна 200Hz ➔ 1800Hz).
- `playReelTickSound()`: четкий механический тик пролетающей ячейки рулетки.
- `playWinSound()`: мажорный 4-нотный арпеджио-аккорд победы (C5, E5, G5, C6).
- `playEpicFanfareSound()`: полифонический многоголосый триумфальный кибер-фанфар при открытии топ-приза.

### 12.2. Контроллер звука
- Кнопка **🔊/🔇** в шапке сайта.
- Состояние сохраняется в БД (`users.sound_enabled`) и синхронизируется при авторизации.

---

## 13. Профиль игрока и статистика

Вкладки личного кабинета (`/profile`):
1. **Overview (Обзор)**:
   - Большой статус-баннер с аватаром, текущим VIP-рангом, прогресс-баром XP.
   - Сводные карточки: игровой баланс, баланс сейфа, оборот, чистый профит, открытые кейсы.
   - Кнопки быстрого вызова: Get SC, Crates, Vault, Tip.
2. **Gameplay History (История)**:
   - Детальный реестр транзакций: депозиты, выигрыши, спины, открытие кейсов, чаевые, реферальные начисления.
3. **Cyber Syndicate**:
   - Панель реферальной программы с QR-кодом, ссылкой, списком рекрутов и доходом.
4. **Personal Info (Личные данные)**:
   - Редактирование адреса, телефона и персональных данных.
5. **Security (Безопасность)**:
   - Смена пароля и управление PIN-кодом сейфа.

---

## 14. Юридический блок и страницы комплаенса

Для работы в модели Social Sweeps Casino сайт оснащен обязательными статическими страницами:
- `/terms` — Пользовательское соглашение (Terms of Service).
- `/privacy` — Политика конфиденциальности (GDPR compliance).
- `/sweeps-rules` — Официальные правила Sweeps (No purchase necessary, альтернативный способ входа AMOE, правило 1x отыгрыша перед выводом).
- `/responsible-gaming` — Страница ответственной игры (самоисключение, лимиты времени).
- `/about` — О платформе Neonwin.
- `/faq` — Ответы на частые вопросы.
- `/contact` — Форма тикетов обратной связи в службу поддержки.

---

## 15. Схема базы данных и миграций

### Основные таблицы:

```sql
-- Таблица пользователей с полями комплаенса, сейфа и рефералов
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    surname VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(30) NOT NULL,
    date_of_birth DATE NOT NULL,
    street VARCHAR(255) NOT NULL,
    city VARCHAR(100) NOT NULL,
    country VARCHAR(100) NOT NULL,
    postcode VARCHAR(30) NOT NULL,
    terms_accepted BOOLEAN DEFAULT TRUE,
    password VARCHAR(255) NOT NULL,
    user_code VARCHAR(50) UNIQUE NOT NULL, -- ZP-123456
    referral_code VARCHAR(50) UNIQUE NOT NULL, -- NW-REF-XXXXXX
    referred_by_id BIGINT UNSIGNED NULL REFERENCES users(id),
    game_balance DECIMAL(12,2) DEFAULT 0.00,
    vault_balance DECIMAL(12,2) DEFAULT 0.00,
    vault_pin VARCHAR(255) NULL,
    vip_xp BIGINT UNSIGNED DEFAULT 0,
    vip_level INT UNSIGNED DEFAULT 1,
    sound_enabled BOOLEAN DEFAULT TRUE,
    last_daily_bonus_at TIMESTAMP NULL,
    avatar VARCHAR(500) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Таблица лутбоксов (Cyber Crates)
CREATE TABLE user_crates (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    type VARCHAR(50) NOT NULL, -- 'recruit', 'wager_milestone', 'vip_levelup'
    name VARCHAR(100) NOT NULL,
    tier VARCHAR(50) NOT NULL, -- 'bronze', 'silver', 'gold', 'platinum', 'mythic'
    status VARCHAR(20) DEFAULT 'pending', -- 'pending', 'opened'
    reward_sc DECIMAL(10,2) NULL,
    reward_xp INT UNSIGNED NULL,
    opened_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Таблица реферальных начислений
CREATE TABLE referral_earnings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    referrer_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    referred_user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    reward_sc DECIMAL(10,2) NOT NULL,
    type VARCHAR(50) NOT NULL, -- 'signup_bonus', 'rakeback_share'
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Таблица P2P чаевых
CREATE TABLE tipping_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    recipient_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    amount_sc DECIMAL(10,2) NOT NULL,
    fee_sc DECIMAL(10,2) NOT NULL,
    net_amount_sc DECIMAL(10,2) NOT NULL,
    note VARCHAR(150) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Таблица испытаний (Live Challenges)
CREATE TABLE live_challenges (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    game_code VARCHAR(100) NOT NULL,
    game_name VARCHAR(100) NOT NULL,
    target_multiplier DECIMAL(8,2) NOT NULL,
    min_bet DECIMAL(8,2) NOT NULL,
    prize_sc DECIMAL(10,2) NOT NULL,
    winner_user_id BIGINT UNSIGNED NULL REFERENCES users(id),
    winner_name VARCHAR(100) NULL,
    winner_multiplier DECIMAL(8,2) NULL,
    status VARCHAR(20) DEFAULT 'active', -- 'active', 'completed', 'expired'
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Таблицы предпочтений (Избранное и Недавние игры)
CREATE TABLE user_favorites (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    game_code VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY (user_id, game_code)
);

CREATE TABLE user_recent_games (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    game_code VARCHAR(100) NOT NULL,
    last_played_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    UNIQUE KEY (user_id, game_code)
);

-- Таблица игровых транзакций шлюза деноминации (Seamless Game Transactions)
CREATE TABLE game_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    provider_tx_id VARCHAR(100) UNIQUE NOT NULL, -- Внешний ID транзакции NexusGGR (для идемпотентности)
    coins_debit BIGINT UNSIGNED DEFAULT 0, -- Списано виртуальных монет
    coins_credit BIGINT UNSIGNED DEFAULT 0, -- Начислено виртуальных монет выигрыша
    response_balance DECIMAL(10,2) NOT NULL, -- Баланс, отданный провайдеру в кредитах
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 16. Чек-лист автоматизированного тестирования (PHPUnit)

Каждый модуль покрыт автоматизированными Feature-тестами:

1. `tests/Feature/AuthTest.php`:
   - Регистрация со всеми 8 блоками данных и валидацией 18+.
   - Блокировка стран из запрещенного списка.
   - Проверка отсутствия стартового 1500 бонуса и корректного создания первого Welcome Crate.
2. `tests/Feature/DepositTest.php`:
   - Зачисление паков в EUR, GBP, USD.
   - Проверка блокировки Daily 1 SC до истечения 24 часов.
3. `tests/Feature/CratesTest.php`:
   - Получение инвентаря кейсов.
   - Открытие кейса со взвешенным RNG и зачислением SC + XP.
   - Защита от повторного открытия одного кейса.
4. `tests/Feature/VaultTest.php`:
   - Депозит в сейф и списание с основного баланса.
   - Снятие средств без PIN и проверка блокировки при неверном PIN-коде.
5. `tests/Feature/SyndicateTest.php`:
   - Регистрация по реферальному коду и начисление 5 SC рефереру.
   - Загрузка дашборда рефералов синдиката.
6. `tests/Feature/TippingTest.php`:
   - Перевод чаевых по коду игрока с вычетом комиссии 3%.
   - Запрет перевода самому себе и при недостаточном балансе.
7. `tests/Feature/GamePreferencesTest.php`:
   - Добавление/удаление из Избранного (❤️).
   - Фиксация недавно запущенных игр.
   - Переключение флага `sound_enabled`.

---

> 💡 **Как использовать эту спецификацию для нового сайта**:
> Достаточно перенести backend-контроллеры, миграции и бизнес-логику из данного репозитория, заменить стилевые CSS-переменные и цветовую палитру в Tailwind на новый брендинг (например, вместо Dark Cyberpunk на Vegas Gold, Neon Retro или Minimalist Crypto), и весь богатый интерактивный функционал заработает автоматически.
