<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class TagResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_resource_structure(): void
    {
        $tag = Tag::factory()->create(['name' => 'important']);

        $resource = (new TagResource($tag))->toArray(new Request());

        $this->assertSame($tag->id, $resource['id']);
        $this->assertSame('important', $resource['name']);
    }
}

