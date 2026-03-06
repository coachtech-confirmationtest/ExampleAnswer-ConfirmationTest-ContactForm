<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_displays_validated_data(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '09012345678',
            'address' => 'Tokyo',
            'building' => 'Sunshine 60',
            'category_id' => $category->id,
            'detail' => 'テスト内容',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->post('/contacts/confirm', $payload);

        $response->assertOk();
        $response->assertViewIs('contact.confirm');
        $response->assertSee('Taro');
        $response->assertSee('Yamada');
        $response->assertSee('taro@example.com');
        $response->assertSee('Support');
    }

    public function test_confirm_validation_error_redirects_back(): void
    {
        $response = $this->post('/contacts/confirm', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'tel', 'address', 'category_id', 'detail']);
    }

    public function test_store_persists_contact_and_redirects_to_thanks(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => 'Taro',
            'last_name' => 'Yamada',
            'gender' => 1,
            'email' => 'taro@example.com',
            'tel' => '0312345678',
            'address' => 'Tokyo',
            'building' => 'Sunshine 60',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->post('/contacts', $payload);

        $response->assertRedirect('/thanks');

        $this->assertDatabaseHas('contacts', [
            'email' => 'taro@example.com',
            'category_id' => $category->id,
        ]);

        $contact = Contact::where('email', 'taro@example.com')->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_store_validation_error_redirects_back(): void
    {
        $response = $this->post('/contacts', []);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['first_name', 'last_name', 'email', 'tel', 'address', 'category_id', 'detail']);
    }
}
