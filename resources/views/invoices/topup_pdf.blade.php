<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $invoiceNumber }} — Neonwin</title>
    <style>
        @page {
            margin: 25px 30px;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #1a1a2e;
            font-size: 12px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid #8b5cf6;
            padding-bottom: 15px;
        }
        .logo-title {
            font-size: 24px;
            font-weight: 900;
            color: #4c1d95;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .logo-accent {
            color: #db2777;
        }
        .invoice-title {
            font-size: 20px;
            font-weight: 800;
            color: #1e1b4b;
            text-align: right;
            text-transform: uppercase;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 25px;
        }
        .meta-box {
            background-color: #f5f3ff;
            border: 1px solid #ddd6fe;
            border-radius: 8px;
            padding: 12px;
            font-size: 11px;
        }
        .meta-box strong {
            color: #5b21b6;
            display: block;
            margin-bottom: 4px;
            font-size: 12px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .items-table th {
            background-color: #4c1d95;
            color: #ffffff;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            padding: 10px 12px;
            text-align: left;
        }
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .items-table tr:nth-child(even) {
            background-color: #faf5ff;
        }
        .total-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .total-table {
            width: 45%;
            float: right;
            border-collapse: collapse;
        }
        .total-table td {
            padding: 6px 10px;
            font-size: 11px;
        }
        .total-table .grand-total {
            background-color: #4c1d95;
            color: #ffffff;
            font-weight: bold;
            font-size: 14px;
        }
        .clear {
            clear: both;
        }
        .status-badge {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            border: 1px solid #86efac;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: bold;
            font-size: 10px;
            text-transform: uppercase;
        }
        .footer {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            font-size: 10px;
            color: #64748b;
            text-align: center;
            line-height: 1.5;
        }
    </style>
</head>
<body>

    <!-- Header -->
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td valign="middle">
                <div class="logo-title">NEON<span class="logo-accent">WIN</span></div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 3px;">Social Sweeps Entertainment</div>
            </td>
            <td valign="middle" align="right">
                <div class="invoice-title">RECEIPT / INVOICE</div>
                <div style="font-size: 11px; color: #6b7280; margin-top: 3px;">Invoice #: <strong>{{ $invoiceNumber }}</strong></div>
                <div style="font-size: 11px; color: #6b7280;">Date: {{ $date }}</div>
            </td>
        </tr>
    </table>

    <!-- Billing Info -->
    <table class="meta-table" cellpadding="0" cellspacing="0">
        <tr>
            <td width="48%" valign="top">
                <div class="meta-box">
                    <strong>Billed To:</strong>
                    {{ $user->name }} {{ $user->surname }}<br>
                    Email: {{ $user->email }}<br>
                    Player Code: {{ $user->user_code }}<br>
                    @if($user->city || $user->country)
                    {{ $user->street ?? '' }} {{ $user->city ?? '' }} {{ $user->postcode ?? '' }}<br>
                    {{ $user->country ?? 'Global' }}
                    @endif
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%" valign="top">
                <div class="meta-box">
                    <strong>Merchant Provider:</strong>
                    Neonwin Ltd.<br>
                    Domain: <a href="https://neonwin.co.uk" style="color: #4c1d95; text-decoration: none;">neonwin.co.uk</a><br>
                    Support: <a href="mailto:info@neonwin.co.uk" style="color: #4c1d95; text-decoration: none;">info@neonwin.co.uk</a><br>
                    Payment Status: <span class="status-badge">PAID IN FULL</span>
                </div>
            </td>
        </tr>
    </table>

    <!-- Line Items -->
    <table class="items-table" cellpadding="0" cellspacing="0">
        <thead>
            <tr>
                <th width="50%">Item & Description</th>
                <th width="20%" align="center">Virtual Coins</th>
                <th width="15%" align="right">Rate</th>
                <th width="15%" align="right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                    <strong style="color: #1e1b4b; font-size: 12px;">{{ $packName }}</strong><br>
                    <span style="color: #64748b; font-size: 10px;">Digital Entertainment Coin Pack Top-Up. Instant In-Game Play Wallet credit.</span>
                </td>
                <td align="center" style="font-family: monospace; font-weight: bold; color: #7c3aed;">
                    {{ number_format($coins) }} Coins
                </td>
                <td align="right">1</td>
                <td align="right" style="font-weight: bold;">
                    {{ $currencySymbol }}{{ number_format($price, 2) }}
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Totals -->
    <div class="total-section">
        <table class="total-table" cellpadding="0" cellspacing="0">
            <tr>
                <td align="left" style="color: #64748b;">Subtotal:</td>
                <td align="right" style="font-weight: bold;">{{ $currencySymbol }}{{ number_format($price, 2) }}</td>
            </tr>
            <tr>
                <td align="left" style="color: #64748b;">Digital VAT (0%):</td>
                <td align="right" style="font-weight: bold;">{{ $currencySymbol }}0.00</td>
            </tr>
            <tr class="grand-total">
                <td align="left">TOTAL PAID:</td>
                <td align="right">{{ $currencySymbol }}{{ number_format($price, 2) }} {{ $currency }}</td>
            </tr>
        </table>
        <div class="clear"></div>
    </div>

    <!-- Footer Note -->
    <div class="footer">
        Thank you for choosing <strong>Neonwin</strong>! All coin purchases are for entertainment and gaming purposes.<br>
        For inquiries or assistance, contact our 24/7 support desk at <a href="mailto:info@neonwin.co.uk" style="color: #6d28d9; text-decoration: none;">info@neonwin.co.uk</a>.<br>
        Neonwin &copy; {{ date('Y') }}. All rights reserved.
    </div>

</body>
</html>
