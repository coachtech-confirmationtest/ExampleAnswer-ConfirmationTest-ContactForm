<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_index_page_is_accessible(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertViewIs('contact.index');
    }

    public function test_contact_thanks_page_is_accessible(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
    }
}

