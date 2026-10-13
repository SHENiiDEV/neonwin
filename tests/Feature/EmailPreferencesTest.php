<?php

namespace Tests\Feature;

use App\Jobs\SendDepositConfirmation;
use App\Mail\WelcomeMail;
use App\Models\Deposit;
use App\Models\User;
use App\Services\VerifiedDepositService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class EmailPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private function registration(array $overrides = []): array
    {
        return array_replace(['name' => 'Alex', 'surname' => 'Player', 'email' => 'alex@example.test', 'password' => 'test-password', 'phone' => '+37120000000', 'date_of_birth' => '1990-01-01', 'street' => 'Example street', 'city' => 'Riga', 'country' => 'Latvia', 'postcode' => 'LV-1000', 'terms' => true], $overrides);
    }

    public function test_existing_users_default_to_account_mail_only_and_can_change_choices(): void
    {
        $user = User::factory()->create(['contact_channel' => 'telegram', 'contact_handle' => '@test_player', 'contact_consent_at' => now()]);
        $other = User::factory()->create();
        $this->assertTrue($user->fresh()->account_emails_enabled);
        $this->assertNull($user->fresh()->marketing_email_consent_at);

        $this->actingAs($user)->put('/profile/preferences', ['account_emails_enabled' => false, 'marketing_emails_enabled' => true, 'messenger_contact_enabled' => false])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->account_emails_enabled);
        $this->assertNotNull($user->fresh()->marketing_email_consent_at);
        $this->assertNull($user->fresh()->contact_consent_at);
        $this->assertTrue($other->fresh()->account_emails_enabled);
        $this->get('/profile?section=preferences')->assertInertia(fn ($page) => $page->where('auth.user.account_emails_enabled', false)->where('auth.user.marketing_emails_enabled', true)->where('auth.user.messenger_contact_enabled', false));

        $this->put('/profile/preferences', ['account_emails_enabled' => false, 'marketing_emails_enabled' => false, 'messenger_contact_enabled' => false])->assertSessionHasNoErrors();
        $this->assertNull($user->fresh()->marketing_email_consent_at);
    }

    public function test_preferences_require_valid_booleans_and_a_saved_messenger(): void
    {
        $user = User::factory()->create();
        $this->putJson('/profile/preferences', ['account_emails_enabled' => false, 'marketing_emails_enabled' => true, 'messenger_contact_enabled' => false])->assertUnauthorized();
        $this->actingAs($user)->putJson('/profile/preferences', ['account_emails_enabled' => 'nope', 'marketing_emails_enabled' => true, 'messenger_contact_enabled' => false])->assertUnprocessable()->assertJsonValidationErrors('account_emails_enabled');
        $this->putJson('/profile/preferences', ['account_emails_enabled' => true, 'marketing_emails_enabled' => false, 'messenger_contact_enabled' => true])->assertUnprocessable()->assertJsonValidationErrors('messenger_contact_enabled');
        $this->assertNull($user->fresh()->marketing_email_consent_at);
    }

    public function test_registration_email_choices_control_welcome_and_marketing_consent(): void
    {
        Mail::fake();
        $this->post('/register', $this->registration(['account_emails_enabled' => false, 'marketing_email_consent' => false]))->assertSessionHasNoErrors();
        $user = User::where('email', 'alex@example.test')->firstOrFail();
        $this->assertFalse($user->account_emails_enabled);
        $this->assertNull($user->marketing_email_consent_at);
        Mail::assertNotSent(WelcomeMail::class);

        $this->post('/logout')->assertRedirect();
        $this->post('/register', $this->registration(['email' => 'second@example.test', 'marketing_email_consent' => true]))->assertSessionHasNoErrors();
        $second = User::where('email', 'second@example.test')->firstOrFail();
        $this->assertTrue($second->account_emails_enabled);
        $this->assertNotNull($second->marketing_email_consent_at);
        Mail::assertSent(WelcomeMail::class, fn (WelcomeMail $mail): bool => $mail->hasTo($second->email));
    }

    public function test_queued_deposit_mail_checks_latest_preference_before_sending(): void
    {
        Queue::fake([SendDepositConfirmation::class]);
        Mail::fake();
        $user = User::factory()->create();
        $deposit = Deposit::factory()->create(['user_id' => $user->id, 'status' => 'pending', 'payment_provider' => 'test-provider', 'amount_cents' => 2500]);

        app(VerifiedDepositService::class)->confirm($deposit->reference, 'test-provider', 'event-1', 2500, 'EUR');
        Queue::assertPushed(SendDepositConfirmation::class, 1);
        $user->forceFill(['account_emails_enabled' => false])->save();
        (new SendDepositConfirmation($deposit->id))->handle();
        Mail::assertNothingSent();

        $this->assertSame(3000, $user->fresh()->eur_balance_cents);
    }

    public function test_admin_player_view_shows_email_preferences(): void
    {
        $player = User::factory()->create(['account_emails_enabled' => false, 'marketing_email_consent_at' => null]);
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();

        $this->actingAs($admin)->get('/admin?view=users&user_id='.$player->id)
            ->assertInertia(fn ($page) => $page->where('records.data.0.account_emails_enabled', false)->where('records.data.0.marketing_email_consent_at', null));
    }
}
