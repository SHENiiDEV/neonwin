@extends('emails.layout')

@section('title', 'Welcome')
@section('preheader', 'Your Neonwin account is ready. Your EUR wallet starts at €0.00.')
@section('category', 'YOUR ACCOUNT')

@section('body')
    <p style="margin:0 0 15px;color:#d8b76b;font-size:11px;font-weight:700;letter-spacing:2.6px;">WELCOME TO NEONWIN</p>
    <h1 class="mail-heading" style="margin:0 0 20px;color:#f8f5fc;font-size:41px;line-height:1.08;font-weight:800;letter-spacing:-1.7px;">The good part<br><span style="color:#d8b76b;">starts here.</span></h1>
    <p style="margin:0 0 24px;color:#c8bccf;font-size:15px;line-height:25px;">Hi {{ $user->name }}, your account is ready. Explore the casino, keep an eye on your VIP progress and make the space your own.</p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 27px;border:1px solid #49375c;background:#21192c;"><tr>
        <td class="mail-pad" style="padding:22px 24px;">
            <p style="margin:0 0 7px;color:#ae98bd;font-size:10px;font-weight:700;letter-spacing:1.8px;">YOUR PLAYER ID</p>
            <p style="margin:0 0 17px;color:#f8f5fc;font-size:19px;font-weight:700;letter-spacing:1px;">{{ $userCode }}</p>
            <p style="margin:0;color:#bdaec8;font-size:13px;line-height:21px;">Your EUR wallet starts at <strong style="color:#f1d58f;">€0.00</strong>. Any available offers and their terms appear in your profile.</p>
        </td>
    </tr></table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="background:#7542df;border-radius:8px;">
        <a href="{{ rtrim(config('app.url'), '/') }}/profile" style="display:inline-block;padding:15px 26px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">Open your profile &nbsp;&#8594;</a>
    </td></tr></table>
    <p style="margin:22px 0 0;color:#9d90aa;font-size:12px;line-height:20px;">If you did not create this account, contact support so we can protect your email address.</p>
@endsection

@section('footer-note', 'This is an account message about your registration.')
