<?php

namespace Tests\Feature\Api\V1;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactApiTest extends TestCase
{
    use RefreshDatabase;

    // ==================== INDEX ====================

    public function test_index_returns_contacts_as_json(): void
    {
        Contact::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'category' => ['id', 'content'],
                    'first_name',
                    'last_name',
                    'gender',
                    'email',
                    'tel',
                    'address',
                    'building',
                    'detail',
                    'tags',
                    'created_at',
                    'updated_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
        $response->assertJsonPath('meta.total', 3);
    }

    public function test_index_paginates_with_default_20_per_page(): void
    {
        Contact::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/contacts');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonCount(20, 'data');
    }

    public function test_index_accepts_custom_per_page(): void
    {
        Contact::factory()->count(10)->create();

        $response = $this->getJson('/api/v1/contacts?per_page=5');

        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 5);
        $response->assertJsonCount(5, 'data');
    }

    public function test_index_filters_by_keyword(): void
    {
        Contact::factory()->create(['first_name' => 'Ken', 'email' => 'ken@example.com']);
        Contact::factory()->create(['first_name' => 'Jane', 'email' => 'jane@example.com']);

        $response = $this->getJson('/api/v1/contacts?keyword=Ken');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.first_name', 'Ken');
    }

    public function test_index_filters_by_gender(): void
    {
        Contact::factory()->create(['gender' => 1]);
        Contact::factory()->create(['gender' => 2]);

        $response = $this->getJson('/api/v1/contacts?gender=1');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.gender', 1);
    }

    public function test_index_filters_by_category_id(): void
    {
        $category = Category::factory()->create();
        Contact::factory()->for($category)->create();
        Contact::factory()->create();

        $response = $this->getJson('/api/v1/contacts?category_id='.$category->id);

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_filters_by_date(): void
    {
        Contact::factory()->create(['created_at' => Carbon::parse('2024-02-01 09:00:00')]);
        Contact::factory()->create(['created_at' => Carbon::parse('2024-02-02 09:00:00')]);

        $response = $this->getJson('/api/v1/contacts?date=2024-02-01');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_index_returns_validation_error_for_invalid_gender(): void
    {
        $response = $this->getJson('/api/v1/contacts?gender=9');

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('gender');
    }

    // ==================== SHOW ====================

    public function test_show_returns_contact_detail(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $tag = Tag::factory()->create(['name' => '質問']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Mika',
            'last_name' => 'Suzuki',
        ]);
        $contact->tags()->attach($tag);

        $response = $this->getJson('/api/v1/contacts/'.$contact->id);

        $response->assertOk();
        $response->assertJsonPath('data.first_name', 'Mika');
        $response->assertJsonPath('data.last_name', 'Suzuki');
        $response->assertJsonPath('data.category.content', 'Support');
        $response->assertJsonPath('data.tags.0.name', '質問');
    }

    public function test_show_returns_404_for_nonexistent_contact(): void
    {
        $response = $this->getJson('/api/v1/contacts/9999');

        $response->assertNotFound();
        $response->assertJson(['error' => 'お問い合わせが見つかりませんでした。']);
    }

    // ==================== STORE ====================

    public function test_store_creates_contact_and_returns_201(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $payload = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です',
            'tag_ids' => $tags->pluck('id')->toArray(),
        ];

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertCreated();
        $response->assertJsonPath('data.first_name', '山田');
        $response->assertJsonPath('data.email', 'yamada@example.com');
        $response->assertJsonPath('data.category.id', $category->id);
        $response->assertJsonCount(2, 'data.tags');

        $this->assertDatabaseHas('contacts', ['email' => 'yamada@example.com']);
        $contact = Contact::where('email', 'yamada@example.com')->first();
        foreach ($tags as $tag) {
            $this->assertDatabaseHas('contact_tag', [
                'contact_id' => $contact->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_store_returns_validation_error_for_missing_fields(): void
    {
        $response = $this->postJson('/api/v1/contacts', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors([
            'first_name', 'last_name', 'gender', 'email', 'tel', 'address', 'category_id', 'detail',
        ]);
    }

    public function test_store_returns_validation_error_for_invalid_email(): void
    {
        $category = Category::factory()->create();

        $payload = [
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'invalid-email',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容',
        ];

        $response = $this->postJson('/api/v1/contacts', $payload);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
    }

    // ==================== UPDATE ====================

    public function test_update_modifies_contact_and_returns_json(): void
    {
        $category = Category::factory()->create();
        $newCategory = Category::factory()->create(['content' => '新カテゴリ']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => '田中',
            'last_name' => '花子',
        ]);
        $newTag = Tag::factory()->create(['name' => '更新済み']);

        $payload = [
            'first_name' => '佐藤',
            'last_name' => '次郎',
            'gender' => 2,
            'email' => 'sato@example.com',
            'tel' => '08011112222',
            'address' => '大阪府大阪市1-2-3',
            'building' => null,
            'category_id' => $newCategory->id,
            'detail' => '更新内容です',
            'tag_ids' => [$newTag->id],
        ];

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, $payload);

        $response->assertOk();
        $response->assertJsonPath('data.first_name', '佐藤');
        $response->assertJsonPath('data.last_name', '次郎');
        $response->assertJsonPath('data.category.content', '新カテゴリ');
        $response->assertJsonCount(1, 'data.tags');
        $response->assertJsonPath('data.tags.0.name', '更新済み');

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'first_name' => '佐藤',
        ]);
    }

    public function test_update_returns_404_for_nonexistent_contact(): void
    {
        $category = Category::factory()->create();

        $payload = [
            'first_name' => '佐藤',
            'last_name' => '次郎',
            'gender' => 1,
            'email' => 'sato@example.com',
            'tel' => '08011112222',
            'address' => '大阪府',
            'category_id' => $category->id,
            'detail' => '内容',
        ];

        $response = $this->putJson('/api/v1/contacts/9999', $payload);

        $response->assertNotFound();
    }

    public function test_update_returns_validation_error_for_invalid_data(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->putJson('/api/v1/contacts/'.$contact->id, []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['first_name', 'last_name']);
    }

    // ==================== DESTROY ====================

    public function test_destroy_deletes_contact_and_returns_204(): void
    {
        $contact = Contact::factory()->create();

        $response = $this->deleteJson('/api/v1/contacts/'.$contact->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_contact(): void
    {
        $response = $this->deleteJson('/api/v1/contacts/9999');

        $response->assertNotFound();
    }
}
