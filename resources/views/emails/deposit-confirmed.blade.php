@extends('emails.layout')

@section('title', 'Deposit confirmed')
@section('preheader', 'Your verified EUR deposit has been credited to your Neonwin wallet.')
@section('category', 'EUR WALLET')

@section('body')
    <p style="margin:0 0 15px;color:#d8b76b;font-size:11px;font-weight:700;letter-spacing:2.6px;">PAYMENT CONFIRMED</p>
    <h1 class="mail-heading" style="margin:0 0 20px;color:#f8f5fc;font-size:39px;line-height:1.12;font-weight:800;letter-spacing:-1.5px;">Your deposit<br><span style="color:#d8b76b;">has landed.</span></h1>
    <p style="margin:0 0 25px;color:#c8bccf;font-size:15px;line-height:25px;">Hi {{ $firstName }}, your verified deposit has been credited to your EUR wallet. You can review the details below and see your latest balance in your profile.</p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 26px;border:1px solid #49375c;background:#21192c;"><tr><td class="mail-pad" style="padding:23px 24px;">
        <p style="margin:0 0 5px;color:#ae98bd;font-size:10px;font-weight:700;letter-spacing:1.8px;">AMOUNT CREDITED</p>
        <p style="margin:0 0 19px;color:#f1d58f;font-size:35px;font-weight:800;letter-spacing:-1px;">€{{ number_format($amountCents / 100, 2) }}</p>
        <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="border-top:1px solid #49375c;">
            <tr><td style="padding:16px 0 5px;color:#ae98bd;font-size:12px;">Reference</td><td align="right" style="padding:16px 0 5px;color:#f8f5fc;font-size:12px;font-weight:700;word-break:break-all;">{{ $reference }}</td></tr>
            <tr><td style="padding:5px 0;color:#ae98bd;font-size:12px;">Confirmed</td><td align="right" style="padding:5px 0;color:#f8f5fc;font-size:12px;">{{ $confirmedAt }} UTC</td></tr>
            <tr><td style="padding:5px 0;color:#ae98bd;font-size:12px;">Currency</td><td align="right" style="padding:5px 0;color:#f8f5fc;font-size:12px;">EUR</td></tr>
        </table>
    </td></tr></table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="background:#7542df;border-radius:8px;">
        <a href="{{ rtrim(config('app.url'), '/') }}/profile" style="display:inline-block;padding:15px 26px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">View your wallet &nbsp;&#8594;</a>
    </td></tr></table>
    <p style="margin:22px 0 0;color:#9d90aa;font-size:12px;line-height:20px;">If you do not recognize this deposit, contact support with the reference above. Please do not reply with card details.</p>
@endsection

@section('footer-note', 'This is a transaction message about a verified EUR deposit.')
