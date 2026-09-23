<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_legal_and_info_pages_load_successfully(): void
    {
        $pages = [
            '/about',
            '/terms',
            '/privacy',
            '/sweeps-rules',
            '/responsible-gaming',
            '/faq',
            '/help',
            '/contact',
        ];

        foreach ($pages as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    public function test_contact_form_submission_success(): void
    {
        $response = $this->post('/contact', [
            'name' => 'John Doe',
            'email' => 'johndoe@example.com',
            'category' => 'general',
            'subject' => 'Question about VIP XP',
            'message' => 'Hello, I would like to know more about the instant cashback payout schedule.',
        ]);

        $response->assertSessionHas('success');
    }
}
