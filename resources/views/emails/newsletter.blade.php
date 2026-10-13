@extends('emails.layout')

@section('title', 'The Neonwin edit')
@section('preheader', 'A closer look at games, the VIP club and your daily wheel.')
@section('category', 'THE NEONWIN EDIT')

@section('body')
    <p style="margin:0 0 15px;color:#d8b76b;font-size:11px;font-weight:700;letter-spacing:2.6px;">{{ strtoupper(now()->format('F Y')) }} / THE NEONWIN EDIT</p>
    <h1 class="mail-heading" style="margin:0 0 20px;color:#f8f5fc;font-size:39px;line-height:1.12;font-weight:800;letter-spacing:-1.5px;">Your next move<br><span style="color:#d8b76b;">starts here.</span></h1>
    <p style="margin:0 0 28px;color:#c8bccf;font-size:15px;line-height:25px;">Your quick guide to what you can explore in Neonwin. Pick up where you left off, check the games catalogue and see how far you've come in the VIP club.</p>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 12px;border:1px solid #49375c;background:#21192c;"><tr><td style="padding:20px 22px;">
        <p style="margin:0 0 7px;color:#d8b76b;font-size:10px;font-weight:700;letter-spacing:1.8px;">01 / DISCOVER</p>
        <p style="margin:0 0 6px;color:#f8f5fc;font-size:18px;font-weight:700;">Find your next favourite</p>
        <p style="margin:0;color:#bdaec8;font-size:13px;line-height:21px;">Browse the games catalogue by provider and find something that fits your mood.</p>
    </td></tr></table>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 12px;border:1px solid #49375c;background:#21192c;"><tr><td style="padding:20px 22px;">
        <p style="margin:0 0 7px;color:#d8b76b;font-size:10px;font-weight:700;letter-spacing:1.8px;">02 / PROGRESS</p>
        <p style="margin:0 0 6px;color:#f8f5fc;font-size:18px;font-weight:700;">Your VIP path</p>
        <p style="margin:0;color:#bdaec8;font-size:13px;line-height:21px;">See your current level, available quests and the rewards you can actually claim.</p>
    </td></tr></table>
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 27px;border:1px solid #49375c;background:#21192c;"><tr><td style="padding:20px 22px;">
        <p style="margin:0 0 7px;color:#d8b76b;font-size:10px;font-weight:700;letter-spacing:1.8px;">03 / CHECK IN</p>
        <p style="margin:0 0 6px;color:#f8f5fc;font-size:18px;font-weight:700;">The daily wheel</p>
        <p style="margin:0;color:#bdaec8;font-size:13px;line-height:21px;">Check whether today's wheel is active and review the prizes and chances before you spin.</p>
    </td></tr></table>

    <table role="presentation" cellspacing="0" cellpadding="0" border="0"><tr><td style="background:#7542df;border-radius:8px;">
        <a href="{{ rtrim(config('app.url'), '/') }}" style="display:inline-block;padding:15px 26px;color:#ffffff;font-size:14px;font-weight:700;text-decoration:none;">Explore Neonwin &nbsp;&#8594;</a>
    </td></tr></table>
    <p style="margin:22px 0 0;color:#9d90aa;font-size:12px;line-height:20px;">Availability and reward terms are always shown on the relevant page. Play within your limits.</p>
@endsection

@section('footer-note', 'Newsletter design preview. No campaign is being sent from this template.')
