<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top-Up Confirmation — Neonwin</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #0b0814;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #ffffff;
        }
        .wrapper {
            width: 100%;
            background-color: #0b0814;
            padding: 40px 15px;
        }
        .container {
            max-width: 580px;
            margin: 0 auto;
            background: linear-gradient(180deg, #181126 0%, #100b1d 100%);
            border: 1px solid #3d2b52;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 20px 50px rgba(0,0,0,0.8);
        }
        .header {
            padding: 35px 30px;
            text-align: center;
            background: linear-gradient(180deg, #25163c 0%, #181126 100%);
            border-bottom: 1px solid #38254b;
        }
        .logo-text {
            font-size: 26px;
            font-weight: 900;
            letter-spacing: 2px;
            text-transform: uppercase;
            color: #ffffff;
        }
        .logo-accent {
            color: #ec4899;
        }
        .content {
            padding: 35px 30px;
        }
        h1 {
            font-size: 22px;
            font-weight: 800;
            margin: 0 0 15px 0;
            color: #ffffff;
        }
        p {
            font-size: 14px;
            line-height: 1.6;
            color: #c7b9db;
            margin: 0 0 20px 0;
        }
        .badge-box {
            background-color: #1a1329;
            border: 1px solid #4a3260;
            border-radius: 14px;
            padding: 20px;
            margin-bottom: 25px;
        }
        .badge-label {
            color: #9d8ba9;
            text-transform: uppercase;
            font-size: 11px;
            font-weight: 700;
        }
        .badge-value {
            color: #facc15;
            font-weight: 800;
            font-family: monospace;
            font-size: 13px;
        }
        .btn-wrap {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #a855f7 0%, #ec4899 100%);
            color: #ffffff !important;
            text-decoration: none;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 14px 32px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(236,72,153,0.4);
        }
        .footer {
            padding: 25px 30px;
            text-align: center;
            background-color: #0d0917;
            border-top: 1px solid #28193a;
            font-size: 11px;
            color: #7d6b8c;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <div class="logo-text">NEON<span class="logo-accent">WIN</span></div>
            </div>
            <div class="content">
                <h1>Coins Added Successfully! ⚡</h1>
                <p>
                    Hello {{ $user->name }}, your purchase of <strong>{{ $packName }}</strong> has been confirmed and credited to your playable wallet balance.
                </p>

                <div class="badge-box">
                    <table width="100%" cellpadding="0" cellspacing="0" border="0">
                        <tr>
                            <td class="badge-label" align="left">Invoice Number:</td>
                            <td class="badge-value" align="right">{{ $invoiceNumber }}</td>
                        </tr>
                        <tr>
                            <td class="badge-label" align="left" style="padding-top: 10px;">Coins Credited:</td>
                            <td class="badge-value" align="right" style="padding-top: 10px; color: #a855f7;">+{{ number_format($coins) }} Coins</td>
                        </tr>
                        <tr>
                            <td class="badge-label" align="left" style="padding-top: 10px;">Amount Paid:</td>
                            <td class="badge-value" align="right" style="padding-top: 10px; color: #38bdf8;">{{ $currencySymbol }}{{ number_format($price, 2) }} {{ $currency }}</td>
                        </tr>
                        <tr>
                            <td class="badge-label" align="left" style="padding-top: 10px;">Updated Balance:</td>
                            <td class="badge-value" align="right" style="padding-top: 10px; color: #facc15;">{{ number_format($newBalance) }} Coins</td>
                        </tr>
                    </table>
                </div>

                <p>
                    📄 <em>A digital PDF receipt is attached to this email for your records.</em>
                </p>

                <div class="btn-wrap">
                    <a href="{{ config('app.url') }}" class="btn">Play Games Now</a>
                </div>
            </div>
            <div class="footer">
                &copy; {{ date('Y') }} Neonwin Ltd. All rights reserved.<br>
                Official Support: <a href="mailto:info@neonwin.co.uk" style="color: #a855f7; text-decoration: none;">info@neonwin.co.uk</a><br>
                Registered in the UK • <a href="https://neonwin.co.uk" style="color: #a855f7; text-decoration: none;">neonwin.co.uk</a>
            </div>
        </div>
    </div>
</body>
</html>
