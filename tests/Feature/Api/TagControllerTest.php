<?php

namespace Tests\Feature\Api;

use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_tags(): void
    {
        $tags = Tag::factory()->count(2)->create();

        $response = $this->getJson('/api/tags');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $tags->first()->id,
            'name' => $tags->first()->name,
        ]);
    }

    public function test_store_creates_tag(): void
    {
        $response = $this->postJson('/api/tags', ['name' => 'priority']);

        $response->assertCreated();
        $this->assertDatabaseHas('tags', ['name' => 'priority']);
    }

    public function test_update_modifies_tag_name(): void
    {
        $tag = Tag::factory()->create(['name' => 'initial']);

        $response = $this->putJson('/api/tags/' . $tag->id, ['name' => 'updated']);

        $response->assertNoContent();
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated']);
    }

    public function test_destroy_deletes_tag(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->deleteJson('/api/tags/' . $tag->id);

        $response->assertNoContent();
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }
}

