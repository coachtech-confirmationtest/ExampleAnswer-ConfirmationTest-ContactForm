<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_export_filtered_contacts(): void
    {
        $user = User::factory()->create();
        $categoryA = Category::factory()->create(['content' => 'Delivery']);
        $categoryB = Category::factory()->create(['content' => 'Exchange']);

        Contact::factory()->for($categoryA)->create([
            'first_name' => 'John',
            'last_name' => 'Smith',
            'gender' => 1,
            'email' => 'john@example.com',
            'created_at' => Carbon::parse('2024-02-10 10:00:00'),
        ]);

        Contact::factory()->for($categoryB)->create([
            'first_name' => 'Alice',
            'last_name' => 'Jones',
            'gender' => 2,
            'email' => 'alice@example.com',
            'created_at' => Carbon::parse('2024-02-11 10:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/contacts/export?keyword=Smith&gender=1&category_id=' . $categoryA->id . '&date=2024-02-10');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        $this->assertStringContainsString('Smith John', $content);
        $this->assertStringContainsString($categoryA->content, $content);
        $this->assertStringNotContainsString('Jones Alice', $content);
    }

    public function test_export_without_filters_returns_all_contacts_in_latest_order(): void
    {
        $user = User::factory()->create();

        $older = Contact::factory()->create([
            'first_name' => 'Eve',
            'last_name' => 'Adams',
            'created_at' => Carbon::parse('2024-02-01 08:00:00'),
        ]);

        $newer = Contact::factory()->create([
            'first_name' => 'Mark',
            'last_name' => 'Brown',
            'created_at' => Carbon::parse('2024-02-02 08:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/contacts/export');

        $response->assertOk();

        $content = $response->streamedContent();

        $this->assertStringContainsString('Adams Eve', $content);
        $this->assertStringContainsString('Brown Mark', $content);

        $lines = array_values(array_filter(explode("\n", trim($content))));
        $firstLine = ltrim($lines[0] ?? '', "\xEF\xBB\xBF");

        $this->assertStringContainsString('Brown Mark', $firstLine);
        $this->assertStringContainsString('Adams Eve', $lines[1] ?? '');
    }
}
