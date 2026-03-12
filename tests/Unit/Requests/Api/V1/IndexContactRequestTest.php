<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\IndexContactRequest;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class IndexContactRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new IndexContactRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_rules_accept_valid_filters(): void
    {
        $category = Category::factory()->create();

        $validator = $this->validator([
            'keyword' => 'Yamada',
            'gender' => 1,
            'category_id' => $category->id,
            'date' => '2024-02-01',
            'per_page' => 50,
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_accept_empty_filters(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_invalid_gender(): void
    {
        $validator = $this->validator([
            'gender' => 0,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->messages());
    }

    public function test_rules_reject_gender_value_9(): void
    {
        $validator = $this->validator([
            'gender' => 9,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->messages());
    }

    public function test_rules_reject_nonexistent_category(): void
    {
        $validator = $this->validator([
            'category_id' => 9999,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category_id', $validator->errors()->messages());
    }

    public function test_rules_reject_invalid_date(): void
    {
        $validator = $this->validator([
            'date' => 'not-a-date',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('date', $validator->errors()->messages());
    }

    public function test_rules_reject_per_page_exceeding_100(): void
    {
        $validator = $this->validator([
            'per_page' => 101,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->messages());
    }

    public function test_rules_reject_per_page_zero(): void
    {
        $validator = $this->validator([
            'per_page' => 0,
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('per_page', $validator->errors()->messages());
    }
}
