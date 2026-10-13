<?php

namespace Tests\Feature;

use App\Jobs\SendDepositConfirmation;
use App\Mail\DepositConfirmedMail;
use App\Mail\NewsletterPreviewMail;
use App\Mail\WelcomeMail;
use App\Models\Deposit;
use App\Models\User;
use App\Services\VerifiedDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_welcome_mail_matches_the_eur_account_and_has_text_fallback(): void
    {
        $user = User::factory()->create(['name' => 'Alex']);
        $mail = new WelcomeMail($user);

        $mail->assertHasSubject('Welcome to Neonwin — your account is ready');
        $mail->assertSeeInHtml('€0.00');
        $mail->assertSeeInHtml($user->user_code);
        $mail->assertSeeInText('EUR wallet: starts at €0.00');
        $mail->assertDontSeeInHtml('0.02 SC');
        $mail->assertDontSeeInHtml('instant payouts');
    }

    public function test_confirmed_deposit_queues_one_accurate_email_and_unavailable_request_queues_none(): void
    {
        Mail::fake();
        Queue::fake([SendDepositConfirmation::class]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/deposit', ['package' => 'eur_25'])->assertStatus(503);
        Queue::assertNotPushed(SendDepositConfirmation::class);

        $deposit = Deposit::factory()->create(['user_id' => $user->id, 'status' => 'pending', 'payment_provider' => 'test-provider', 'amount_cents' => 2500]);
        $service = app(VerifiedDepositService::class);
        $service->confirm($deposit->reference, 'test-provider', 'event-1', 2500, 'EUR');
        $service->confirm($deposit->reference, 'test-provider', 'event-1', 2500, 'EUR');

        Queue::assertPushed(SendDepositConfirmation::class, 1);
        Queue::assertPushed(SendDepositConfirmation::class, fn (SendDepositConfirmation $job): bool => $job->depositId === $deposit->id);
        (new SendDepositConfirmation($deposit->id))->handle();
        Mail::assertSent(DepositConfirmedMail::class, fn (DepositConfirmedMail $mail): bool => $mail->amountCents === 2500 && $mail->reference === $deposit->reference && $mail->hasTo($user->email));
        $mail = new DepositConfirmedMail('Alex', 2500, $deposit->reference, '13 Oct 2026, 10:00');
        $mail->assertSeeInHtml('€25.00');
        $mail->assertSeeInHtml($deposit->reference);
        $mail->assertSeeInText('Amount credited: €25.00');
        $mail->assertDontSeeInHtml('Coins');
    }

    public function test_admin_can_preview_emails_but_player_cannot_and_preview_sends_nothing(): void
    {
        Mail::fake();
        $player = User::factory()->create();
        $this->actingAs($player)->get('/admin/emails')->assertForbidden();
        $this->get('/admin/emails/preview/newsletter')->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin)->get('/admin/emails')->assertInertia(fn ($page) => $page->component('AdminEmails'));
        $this->get('/admin/emails/preview/welcome')->assertOk()->assertSee('The good part');
        $this->get('/admin/emails/preview/deposit')->assertOk()->assertSee('€50.00');
        $this->get('/admin/emails/preview/newsletter')->assertOk()->assertSee('The Neonwin edit');
        $this->get('/admin/emails/preview/unknown')->assertNotFound();
        Mail::assertNothingSent();
        Mail::assertNothingQueued();
    }

    public function test_newsletter_is_a_renderable_draft_with_text_fallback(): void
    {
        $mail = new NewsletterPreviewMail;

        $mail->assertSeeInHtml('Your next move');
        $mail->assertSeeInText('The Neonwin edit');
        $mail->assertSeeInHtml('No campaign is being sent');
    }
}
