<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_tag(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/tags', ['name' => 'priority']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['name' => 'priority']);
    }

    public function test_authenticated_user_can_update_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'initial']);

        $response = $this->actingAs($user)->put('/admin/tags/' . $tag->id, ['name' => 'updated']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated']);
    }

    public function test_authenticated_user_can_delete_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/tags/' . $tag->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_unauthenticated_user_cannot_create_tag(): void
    {
        $response = $this->post('/admin/tags', ['name' => 'priority']);

        $response->assertRedirect('/login');
    }
}
