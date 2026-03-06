<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_admin_dashboard(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewIs('admin.index');
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get('/admin');

        $response->assertRedirect('/login');
    }

    public function test_index_displays_contacts_with_filter(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => 'Delivery']);

        $matching = Contact::factory()->for($category)->create([
            'first_name' => 'Ken',
            'last_name' => 'Ito',
            'gender' => 1,
            'email' => 'ken@example.com',
            'created_at' => Carbon::parse('2024-02-01 09:00:00'),
        ]);

        Contact::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'gender' => 2,
            'email' => 'jane@example.com',
            'created_at' => Carbon::parse('2024-02-02 09:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/admin?keyword=Ken&gender=1&category_id=' . $category->id . '&date=2024-02-01');

        $response->assertOk();
        $response->assertSee('Ken');
        $response->assertDontSee('Jane');
    }

    public function test_index_paginates_results(): void
    {
        $user = User::factory()->create();
        Contact::factory()->count(10)->create();

        $response = $this->actingAs($user)->get('/admin');

        $response->assertOk();
        $response->assertViewHas('contacts');
        $this->assertEquals(7, $response->viewData('contacts')->count());
    }

    public function test_show_displays_contact_detail(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Mika',
            'last_name' => 'Suzuki',
        ]);

        $response = $this->actingAs($user)->get('/admin/contacts/' . $contact->id);

        $response->assertOk();
        $response->assertViewIs('admin.show');
        $response->assertSee('Mika');
        $response->assertSee('Suzuki');
        $response->assertSee('Support');
    }

    public function test_destroy_removes_contact_and_redirects(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/contacts/' . $contact->id);

        $response->assertRedirect('/admin');
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }
}
