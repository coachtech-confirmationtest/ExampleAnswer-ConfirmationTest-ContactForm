<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_category_collection(): void
    {
        $categories = Category::factory()->count(2)->create();

        $response = $this->getJson('/api/categories');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $categories->first()->id,
            'content' => $categories->first()->content,
        ]);
    }
}

