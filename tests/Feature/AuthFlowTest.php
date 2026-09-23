<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_full_profile_and_zero_initial_bonus(): void
    {
        $response = $this->post('/register', [
            'name' => 'Alexander',
            'surname' => 'Wright',
            'email' => 'alex.wright@example.com',
            'password' => 'SecurePass123!',
            'phone' => '+447911123456',
            'date_of_birth' => '1995-06-15',
            'street' => '221B Baker Street, Flat 2',
            'city' => 'London',
            'country' => 'United Kingdom',
            'postcode' => 'NW1 6XE',
            'terms' => '1',
        ]);

        $response->assertSessionHas('success');
        $this->assertAuthenticated();

        $user = User::where('email', 'alex.wright@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('Alexander', $user->name);
        $this->assertEquals('Wright', $user->surname);
        $this->assertEquals('+447911123456', $user->phone);
        $this->assertEquals('1995-06-15', $user->date_of_birth->format('Y-m-d'));
        $this->assertEquals('221B Baker Street, Flat 2', $user->street);
        $this->assertEquals('London', $user->city);
        $this->assertEquals('United Kingdom', $user->country);
        $this->assertEquals('NW1 6XE', $user->postcode);
        $this->assertTrue((bool) $user->terms_accepted);
        $this->assertEquals(0.00, $user->game_balance);
        $this->assertEquals(0, $user->vip_xp);
    }

    public function test_user_cannot_register_from_restricted_country(): void
    {
        $response = $this->post('/register', [
            'name' => 'Ivan',
            'surname' => 'Petrov',
            'email' => 'ivan@example.com',
            'password' => 'SecurePass123!',
            'phone' => '+79991234567',
            'date_of_birth' => '1990-01-01',
            'street' => 'Tverskaya 1',
            'city' => 'Moscow',
            'country' => 'Russia',
            'postcode' => '101000',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['country']);
        $this->assertGuest();
    }

    public function test_user_cannot_register_under_18(): void
    {
        $underageDate = now()->subYears(17)->format('Y-m-d');

        $response = $this->post('/register', [
            'name' => 'Minor',
            'surname' => 'User',
            'email' => 'minor@example.com',
            'password' => 'SecurePass123!',
            'phone' => '+447911123456',
            'date_of_birth' => $underageDate,
            'street' => '12 High Street',
            'city' => 'Manchester',
            'country' => 'United Kingdom',
            'postcode' => 'M1 1AA',
            'terms' => '1',
        ]);

        $response->assertSessionHasErrors(['date_of_birth']);
        $this->assertGuest();
    }

    public function test_user_cannot_register_without_accepting_terms(): void
    {
        $response = $this->post('/register', [
            'name' => 'NoTerms',
            'surname' => 'User',
            'email' => 'noterms@example.com',
            'password' => 'SecurePass123!',
            'phone' => '+447911123456',
            'date_of_birth' => '1992-05-10',
            'street' => '12 High Street',
            'city' => 'Manchester',
            'country' => 'United Kingdom',
            'postcode' => 'M1 1AA',
        ]);

        $response->assertSessionHasErrors(['terms']);
        $this->assertGuest();
    }

    public function test_user_can_login(): void
    {
        User::create([
            'name' => 'ExistingUser',
            'email' => 'existing@neonwin.com',
            'password' => bcrypt('secret1234'),
            'game_balance' => 2500.00,
        ]);

        $response = $this->post('/login', [
            'email' => 'existing@neonwin.com',
            'password' => 'secret1234',
        ]);

        $response->assertSessionHas('success');
        $this->assertAuthenticated();
    }

    public function test_demo_login(): void
    {
        $response = $this->post('/demo-login');

        $response->assertSessionHas('success');
        $this->assertAuthenticated();
    }
}
