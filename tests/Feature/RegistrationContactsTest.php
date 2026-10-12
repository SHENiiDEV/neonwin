<?php

namespace Tests\Feature;

use App\Jobs\SendRegistrationNotice;
use App\Models\LaunchSetting;
use App\Models\RegistrationNotice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RegistrationContactsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Http::preventStrayRequests();
    }

    private function data(): array
    {
        return ['name' => 'Test', 'surname' => 'Player', 'email' => 'player@example.test', 'password' => 'test-password', 'phone' => '+37120000000', 'date_of_birth' => '1990-01-01', 'street' => 'Example street', 'city' => 'Riga', 'country' => 'Latvia', 'postcode' => 'LV-1000', 'terms' => true];
    }

    private function configure(): void
    {
        LaunchSetting::find(1)->update(['telegram_enabled' => true, 'telegram_token' => '123456:test_token_abcdefghijklmnopqrstuvwxyz', 'telegram_group_id' => '-100123456789']);
    }

    public function test_registration_stores_contact_encrypted_and_queues_only_a_notice_identifier(): void
    {
        Queue::fake();
        $this->post('/register', array_merge($this->data(), ['contact_channel' => 'whatsapp', 'contact_handle' => '+37121111111', 'contact_consent' => true]))->assertSessionHasNoErrors();
        $user = User::where('email', 'player@example.test')->firstOrFail();
        $this->assertSame('+37121111111', $user->contact_handle);
        $this->assertNotNull($user->contact_consent_at);
        $this->assertNotSame($user->contact_handle, DB::table('users')->where('id', $user->id)->value('contact_handle'));
        $this->assertArrayNotHasKey('contact_handle', $user->toArray());
        Queue::assertPushed(SendRegistrationNotice::class, fn ($job) => $job->noticeId === RegistrationNotice::first()->id);
        Http::assertNothingSent();
    }

    public function test_invalid_contacts_do_not_create_user_and_contact_consent_is_never_assumed(): void
    {
        $this->postJson('/register', array_merge($this->data(), ['contact_channel' => 'whatsapp', 'contact_handle' => '123']))->assertUnprocessable()->assertJsonValidationErrors('contact_handle');
        $this->postJson('/register', array_merge($this->data(), ['contact_channel' => 'telegram', 'contact_handle' => 'https://evil.test']))->assertUnprocessable();
        $this->assertDatabaseCount('users', 0);
        $this->post('/register', array_merge($this->data(), ['contact_channel' => 'telegram', 'contact_handle' => '@test_player']))->assertSessionHasNoErrors();
        $this->assertNull(User::first()->contact_consent_at);
        $this->assertSame('pending', RegistrationNotice::first()->status);
    }

    public function test_registration_without_messenger_still_works(): void
    {
        $this->post('/register', array_merge($this->data(), ['contact_channel' => '', 'contact_handle' => '', 'contact_consent' => false]))->assertSessionHasNoErrors();
        $this->assertNull(User::first()->contact_handle);
        $this->assertDatabaseCount('registration_notices', 1);
    }

    public function test_bot_sends_only_registration_contact_data_and_does_not_resend_success(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        $user = User::factory()->create(['contact_channel' => 'telegram', 'contact_handle' => '@test_player', 'contact_consent_at' => now(), 'street' => 'DO_NOT_SEND_ADDRESS']);
        $notice = RegistrationNotice::factory()->create(['user_id' => $user->id]);
        $job = new SendRegistrationNotice($notice->id);
        $job->handle();
        $job->handle();
        Http::assertSentCount(1);
        Http::assertSent(fn ($request) => $request['chat_id'] === '-100123456789' && str_contains($request['text'], '@test_player') && str_contains($request['text'], $user->email) && ! str_contains($request['text'], 'DO_NOT_SEND_ADDRESS') && ! str_contains($request['text'], $user->password) && $request['protect_content'] === true);
        $this->assertSame('sent', $notice->fresh()->status);
        $this->assertNotNull($notice->fresh()->sent_at);
    }

    public function test_bot_failure_does_not_leak_credentials_or_break_registration(): void
    {
        $this->configure();
        Http::fake(['api.telegram.org/*' => Http::failedConnection('Secret URL with bot token must never be logged')]);
        $notice = RegistrationNotice::factory()->create();
        (new SendRegistrationNotice($notice->id))->handle();
        $this->assertSame('uncertain', $notice->fresh()->status);
        $this->assertStringNotContainsString('Secret URL', $notice->fresh()->error);
        $this->assertNull($notice->fresh()->sent_at);
        (new SendRegistrationNotice($notice->id))->handle();
        Http::assertSentCount(1);
    }

    public function test_admin_can_configure_encrypted_bot_but_token_is_never_in_props_audit_or_old_input(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $token = '123456:test_token_abcdefghijklmnopqrstuvwxyz';
        $this->actingAs($admin)->post('/admin/launch/telegram', ['telegram_enabled' => true, 'telegram_token' => $token, 'telegram_group_id' => '-100123456789'])->assertSessionHasNoErrors();
        $settings = LaunchSetting::find(1);
        $this->assertSame($token, $settings->telegram_token);
        $this->assertNotSame($token, DB::table('launch_settings')->value('telegram_token'));
        $response = $this->get('/admin/launch');
        $response->assertInertia(fn ($page) => $page->missing('settings.telegram_token')->where('telegram.configured', true));
        $this->assertStringNotContainsString($token, $response->getContent());
        $this->assertStringNotContainsString($token, DB::table('admin_audits')->get()->toJson());
        $this->post('/admin/launch/telegram', ['telegram_enabled' => true, 'telegram_token' => $token, 'telegram_group_id' => 'bad'])->assertSessionHasErrors('telegram_group_id')->assertSessionMissing('_old_input.telegram_token');
        $this->post('/admin/launch/telegram', ['telegram_enabled' => true, 'telegram_token' => '', 'telegram_group_id' => '-100123456789'])->assertSessionHasNoErrors();
        $this->assertSame($token, $settings->fresh()->telegram_token);
        Http::assertNothingSent();
    }

    public function test_retry_requires_admin_and_confirmation_and_keeps_sent_notices_unchanged(): void
    {
        $notice = RegistrationNotice::factory()->create();
        $this->actingAs(User::factory()->create())->post('/admin/launch/notices/'.$notice->id.'/retry', ['confirmed' => true])->assertForbidden();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->configure();
        Queue::fake();
        $this->actingAs($admin)->postJson('/admin/launch/notices/'.$notice->id.'/retry')->assertUnprocessable();
        $this->post('/admin/launch/notices/'.$notice->id.'/retry', ['confirmed' => true])->assertSessionHasNoErrors();
        Queue::assertPushed(SendRegistrationNotice::class, 1);
        $notice->update(['status' => 'sent', 'sent_at' => now()]);
        $this->post('/admin/launch/notices/'.$notice->id.'/retry', ['confirmed' => true])->assertSessionHasNoErrors();
        Queue::assertPushed(SendRegistrationNotice::class, 1);
    }
}
