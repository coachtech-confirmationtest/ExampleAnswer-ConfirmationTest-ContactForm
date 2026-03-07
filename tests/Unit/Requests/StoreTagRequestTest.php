<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreTagRequestTest extends TestCase
{
    use RefreshDatabase;

    private function validator(array $data)
    {
        $request = new StoreTagRequest();
        return Validator::make($data, $request->rules());
    }

    public function test_rules_accept_valid_name(): void
    {
        $validator = $this->validator(['name' => 'new-tag']);

        $this->assertTrue($validator->passes());
    }

    public function test_rules_reject_empty_name(): void
    {
        $validator = $this->validator(['name' => '']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_rules_reject_name_exceeding_max_length(): void
    {
        $validator = $this->validator(['name' => str_repeat('a', 51)]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }

    public function test_rules_reject_duplicate_name(): void
    {
        Tag::factory()->create(['name' => 'duplicate']);

        $validator = $this->validator(['name' => 'duplicate']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('name', $validator->errors()->messages());
    }
}

