<?php

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateTagRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_rules_allow_current_name_but_reject_duplicates(): void
    {
        $existing = Tag::factory()->create(['name' => 'existing']);
        $target = Tag::factory()->create(['name' => 'current']);

        $request = new class($target) extends UpdateTagRequest
        {
            public function __construct(private Tag $boundTag)
            {
            }

            public function route($param = null, $default = null)
            {
                if ($param === 'tag') {
                    return $this->boundTag;
                }

                return $default;
            }
        };

        $currentValidator = Validator::make(['name' => 'current'], $request->rules());
        $this->assertTrue($currentValidator->passes());

        $duplicateValidator = Validator::make(['name' => 'existing'], $request->rules());
        $this->assertTrue($duplicateValidator->fails());
        $this->assertArrayHasKey('name', $duplicateValidator->errors()->messages());
    }
}
