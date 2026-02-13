<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CategoryResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_resource_structure(): void
    {
        $category = Category::factory()->create(['content' => 'Delivery Issues']);

        $resource = (new CategoryResource($category))->toArray(new Request());

        $this->assertSame($category->id, $resource['id']);
        $this->assertSame('Delivery Issues', $resource['content']);
    }
}

