<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>@yield('title') · Neonwin</title>
    <style>
        @media only screen and (max-width: 620px) {
            .mail-shell { width: 100% !important; }
            .mail-pad { padding-left: 24px !important; padding-right: 24px !important; }
            .mail-heading { font-size: 33px !important; line-height: 1.15 !important; }
            .mail-stack { display: block !important; width: 100% !important; padding-bottom: 12px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background:#0d0f16;color:#f5f1f9;font-family:Arial,Helvetica,sans-serif;-webkit-text-size-adjust:100%;">
    <div style="display:none;font-size:1px;color:#0d0f16;line-height:1px;max-height:0;max-width:0;opacity:0;overflow:hidden;">@yield('preheader')</div>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="background:#0d0f16;">
        <tr><td align="center" style="padding:36px 12px 48px;">
            <table role="presentation" class="mail-shell" cellspacing="0" cellpadding="0" border="0" width="600" style="width:600px;max-width:600px;border:1px solid #3b2a4e;background:#17121f;border-collapse:separate;">
                <tr><td height="4" style="height:4px;background:#d8b76b;font-size:1px;line-height:1px;">&nbsp;</td></tr>
                <tr><td class="mail-pad" style="padding:31px 44px 26px;border-bottom:1px solid #35283e;">
                    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"><tr>
                        <td valign="middle" style="font-size:26px;font-weight:800;letter-spacing:-1.4px;color:#f5f1f9;">&#9889; neon<span style="color:#a77afa;">win</span></td>
                        <td valign="middle" align="right" style="font-size:10px;font-weight:700;letter-spacing:2px;color:#d8b76b;">@yield('category')</td>
                    </tr></table>
                </td></tr>
                <tr><td class="mail-pad" style="padding:42px 44px 44px;">
                    @yield('body')
                </td></tr>
                <tr><td class="mail-pad" style="padding:24px 44px;border-top:1px solid #35283e;background:#120f19;">
                    <p style="margin:0 0 8px;color:#b8a9c4;font-size:12px;line-height:19px;">@yield('footer-note')</p>
                    <p style="margin:0;color:#8f829b;font-size:11px;line-height:18px;">Neonwin · Adults 18+ · Play responsibly<br>
                    <a href="{{ rtrim(config('app.url'), '/') }}/contact" style="color:#c5a9ff;text-decoration:underline;">Contact support</a> &nbsp;·&nbsp; <a href="{{ rtrim(config('app.url'), '/') }}/profile?section=preferences" style="color:#c5a9ff;text-decoration:underline;">Email preferences</a> &nbsp;·&nbsp; <a href="{{ rtrim(config('app.url'), '/') }}/responsible-gaming" style="color:#c5a9ff;text-decoration:underline;">Responsible gaming</a></p>
                </td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>
