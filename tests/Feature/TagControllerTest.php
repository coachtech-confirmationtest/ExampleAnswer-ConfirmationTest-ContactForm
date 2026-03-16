<?php

namespace Tests\Feature;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_edit_page(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create(['name' => 'test-tag']);

        $response = $this->actingAs($user)->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertStatus(200);
        $response->assertViewIs('admin.tags.edit');
        $response->assertSee('test-tag');
    }

    public function test_unauthenticated_user_cannot_view_edit_page(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get('/admin/tags/'.$tag->id.'/edit');

        $response->assertRedirect('/login');
    }

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

        $response = $this->actingAs($user)->put('/admin/tags/'.$tag->id, ['name' => 'updated']);

        $response->assertRedirect('/admin');
        $this->assertDatabaseHas('tags', ['id' => $tag->id, 'name' => 'updated']);
    }

    public function test_authenticated_user_can_delete_tag(): void
    {
        $user = User::factory()->create();
        $tag = Tag::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/tags/'.$tag->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('tags', ['id' => $tag->id]);
    }

    public function test_unauthenticated_user_cannot_create_tag(): void
    {
        $response = $this->post('/admin/tags', ['name' => 'priority']);

        $response->assertRedirect('/login');
    }
}
