<?php

namespace Tests\Feature;

use App\Models\KycSubmission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KycAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_upload_review_resubmission_and_disabled_withdrawal(): void
    {
        Storage::fake('kyc');
        Http::preventStrayRequests();
        $owner = User::factory()->create(['eur_balance_cents' => 5000]);
        $other = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($owner)->postJson('/withdraw')->assertForbidden();
        $this->post('/verification', ['document' => UploadedFile::fake()->image('passport.jpg')])->assertSessionHas('success');
        $submission = KycSubmission::first();
        $this->assertSame('incomplete', $submission->status);
        $this->actingAs($admin)->post("/admin/kyc/{$submission->id}", ['status' => 'approved', 'version' => $submission->version])->assertUnprocessable();
        $this->actingAs($owner)->post('/verification', ['selfie' => UploadedFile::fake()->image('selfie.jpg')])->assertSessionHas('success');
        $submission->refresh();
        $oldPath = $submission->document_path;
        Storage::disk('kyc')->assertExists($oldPath);
        $this->assertArrayNotHasKey('document_path', $submission->toArray());
        $this->actingAs($other)->get("/verification/{$submission->id}/document")->assertNotFound();
        $this->actingAs($owner)->get("/verification/{$submission->id}/document")->assertDownload();
        $this->actingAs($admin)->get("/verification/{$submission->id}/selfie")->assertDownload();
        $this->post("/admin/kyc/{$submission->id}", ['status' => 'approved', 'version' => $submission->version])->assertSessionHas('success');
        $this->actingAs($owner)->get('/profile')->assertInertia(fn ($page) => $page->where('auth.user.kyc_approved', true));
        $this->postJson('/withdraw', ['amount' => 10])->assertStatus(503);
        $this->assertSame(5000, $owner->fresh()->eur_balance_cents);
        $this->post('/verification', ['document' => UploadedFile::fake()->image('replacement.png')])->assertSessionHas('success');
        $this->assertFalse($owner->hasApprovedKyc());
        $this->assertSame('pending', $submission->fresh()->status);
        Storage::disk('kyc')->assertMissing($oldPath);
        $this->actingAs($admin)->post("/admin/kyc/{$submission->id}", ['status' => 'approved', 'version' => $submission->version])->assertConflict();
        $this->assertDatabaseHas('admin_audits', ['action' => 'kyc_review', 'actor_id' => $admin->id]);
        Http::assertNothingSent();
    }

    public function test_rejection_requires_reason_and_invalid_upload_does_not_replace_files(): void
    {
        Storage::fake('kyc');
        $user = User::factory()->create();
        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($user)->post('/verification', ['document' => UploadedFile::fake()->image('id.png'), 'selfie' => UploadedFile::fake()->image('selfie.png')])->assertSessionHas('success');
        $submission = KycSubmission::first();
        $this->postJson('/verification', ['selfie' => UploadedFile::fake()->create('bad.svg', 10, 'image/svg+xml')])->assertUnprocessable();
        $this->actingAs($admin)->postJson("/admin/kyc/{$submission->id}", ['status' => 'rejected', 'version' => $submission->version])->assertUnprocessable();
        $this->post("/admin/kyc/{$submission->id}", ['status' => 'rejected', 'reason' => 'Document details are unreadable.', 'version' => $submission->version])->assertSessionHas('success');
        $this->assertDatabaseHas('kyc_submissions', ['status' => 'rejected', 'reason' => 'Document details are unreadable.']);
    }

    public function test_every_admin_route_requires_admin_and_client_cannot_grant_role(): void
    {
        $user = User::factory()->create();
        $this->getJson('/admin')->assertUnauthorized();
        $this->actingAs($user)->get('/admin')->assertForbidden();
        foreach (['operations', 'sync', 'agent-balance', 'game-log'] as $path) {
            $this->postJson('/admin/'.$path, [])->assertForbidden();
        }
        $this->post('/profile', ['name' => 'New name', 'is_admin' => true])->assertSessionHas('success');
        $this->assertFalse($user->fresh()->is_admin);
    }

    public function test_cli_provisioning_has_no_default_credentials_and_audits_role_changes(): void
    {
        $user = User::factory()->create();
        $this->artisan('neonwin:admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);
        $this->actingAs($user->fresh())->get('/admin')->assertInertia(fn ($page) => $page->component('Admin')->has('stats')->has('records'));
        $this->artisan('neonwin:admin', ['email' => $user->email, '--revoke' => true])->assertSuccessful();
        $this->assertFalse($user->fresh()->is_admin);
        $this->assertDatabaseCount('admin_audits', 2);
    }
}
