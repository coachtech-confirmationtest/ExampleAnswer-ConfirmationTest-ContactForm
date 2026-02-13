<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_applies_all_available_filters(): void
    {
        $category = Category::factory()->create(['content' => 'Delivery']);
        $otherCategory = Category::factory()->create(['content' => 'Other']);

        $matching = Contact::factory()->for($category)->create([
            'first_name' => 'Ken',
            'last_name' => 'Ito',
            'gender' => 1,
            'email' => 'ken@example.com',
            'created_at' => Carbon::parse('2024-02-01 09:00:00'),
        ]);

        Contact::factory()->for($otherCategory)->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 2,
            'email' => 'jane@example.com',
            'created_at' => Carbon::parse('2024-02-02 09:00:00'),
        ]);

        $tag = Tag::factory()->create();
        $matching->tags()->attach($tag);

        $response = $this->getJson('/api/contacts?keyword=Ken&gender=1&category_id=' . $category->id . '&date=2024-02-01');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.id', $matching->id);
        $response->assertJsonPath('data.0.category.id', $category->id);
        $response->assertJsonPath('meta.total', 1);
    }

    public function test_store_persists_contact_and_attaches_tags(): void
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

        $response = $this->postJson('/api/contacts', $payload);

        $response->assertCreated();

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

    public function test_show_returns_single_contact(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Mika',
            'last_name' => 'Suzuki',
        ]);

        $response = $this->getJson('/api/contacts/' . $contact->id);

        $response->assertOk();
        $response->assertJsonPath('data.id', $contact->id);
        $response->assertJsonPath('data.category.id', $category->id);
    }

    public function test_destroy_removes_contact(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson('/api/contacts/' . $contact->id);

        $response->assertNoContent();

        $this->assertDatabaseMissing('contacts', [
            'id' => $contact->id,
        ]);
    }
}

