<?php

namespace App\Http\Controllers;

use App\Mail\DepositConfirmedMail;
use App\Mail\NewsletterPreviewMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AdminMailPreviewController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('AdminEmails');
    }

    public function show(string $type): Response
    {
        $mail = match ($type) {
            'welcome' => new WelcomeMail(new User(['name' => 'Alex', 'user_code' => 'NW-DEMO-001'])),
            'deposit' => new DepositConfirmedMail('Alex', 5000, 'preview-deposit-2026', now()->utc()->format('d M Y, H:i')),
            'newsletter' => new NewsletterPreviewMail,
        };

        return response($mail->render())->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
