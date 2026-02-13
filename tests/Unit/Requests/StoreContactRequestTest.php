<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreContactRequest;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new StoreContactRequest();
        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function basePayload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Hanako',
            'last_name' => 'Sato',
            'gender' => 2,
            'email' => 'hanako@example.com',
            'tel' => '0312345678',
            'address' => 'Tokyo',
            'building' => 'Skytree',
            'category_id' => $category->id,
            'detail' => 'テストお問い合わせ',
        ], $overrides);
    }

    public function test_rules_accept_valid_payload_with_tags(): void
    {
        $category = Category::factory()->create();
        $tags = Tag::factory()->count(2)->create();

        $validator = $this->validator($this->basePayload($category, [
            'tag_ids' => $tags->pluck('id')->toArray(),
        ]));

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_invalid_phone_number(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validator($this->basePayload($category, [
            'tel' => '123-456',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->messages());
    }
}

