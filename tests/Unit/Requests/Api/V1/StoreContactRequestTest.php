<?php

namespace Tests\Unit\Requests\Api\V1;

use App\Http\Requests\Api\V1\StoreContactRequest;
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

    private function validData(array $overrides = []): array
    {
        $category = Category::factory()->create();

        return array_merge([
            'first_name' => '山田',
            'last_name' => '太郎',
            'gender' => 1,
            'email' => 'yamada@example.com',
            'tel' => '09012345678',
            'address' => '東京都渋谷区1-1-1',
            'building' => '渋谷ビル301',
            'category_id' => $category->id,
            'detail' => 'お問い合わせ内容です',
        ], $overrides);
    }

    public function test_rules_accept_valid_data(): void
    {
        $validator = $this->validator($this->validData());

        $this->assertTrue($validator->passes());
    }

    public function test_rules_accept_valid_data_with_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();
        $data = $this->validData(['tag_ids' => $tags->pluck('id')->toArray()]);

        $validator = $this->validator($data);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_missing_required_fields(): void
    {
        $validator = $this->validator([]);

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->messages();
        $this->assertArrayHasKey('first_name', $errors);
        $this->assertArrayHasKey('last_name', $errors);
        $this->assertArrayHasKey('gender', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('tel', $errors);
        $this->assertArrayHasKey('address', $errors);
        $this->assertArrayHasKey('category_id', $errors);
        $this->assertArrayHasKey('detail', $errors);
    }

    public function test_rules_reject_invalid_email(): void
    {
        $validator = $this->validator($this->validData(['email' => 'invalid-email']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->messages());
    }

    public function test_rules_reject_invalid_tel(): void
    {
        $validator = $this->validator($this->validData(['tel' => '123']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('tel', $validator->errors()->messages());
    }

    public function test_rules_reject_detail_exceeding_120_chars(): void
    {
        $validator = $this->validator($this->validData(['detail' => str_repeat('あ', 121)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('detail', $validator->errors()->messages());
    }

    public function test_rules_reject_invalid_gender(): void
    {
        $validator = $this->validator($this->validData(['gender' => 0]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('gender', $validator->errors()->messages());
    }
}
