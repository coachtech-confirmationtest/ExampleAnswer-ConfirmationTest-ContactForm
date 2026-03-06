<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
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
        $response->assertViewHas('categories');
        $response->assertViewHas('tags');
    }

    public function test_contact_index_page_displays_categories_and_tags(): void
    {
        $category = Category::factory()->create(['content' => 'Delivery']);
        $tag = Tag::factory()->create(['name' => 'urgent']);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('Delivery');
        $response->assertSee('urgent');
    }

    public function test_contact_thanks_page_is_accessible(): void
    {
        $response = $this->get('/thanks');

        $response->assertOk();
        $response->assertViewIs('contact.thanks');
    }
}
