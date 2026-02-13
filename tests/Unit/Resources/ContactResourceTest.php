<?php

namespace Tests\Unit\Resources;

use App\Http\Resources\ContactResource;
use App\Models\Category;
use App\Models\Contact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ContactResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_resource_contains_expected_fields(): void
    {
        $category = Category::factory()->create(['content' => 'Support']);
        $contact = Contact::factory()->for($category)->create([
            'first_name' => 'Saya',
            'last_name' => 'Tanaka',
            'building' => 'Blue Tower',
        ]);

        $contact->setRelation('category', $category);

        $resource = (new ContactResource($contact))->toArray(new Request());

        $this->assertSame($contact->id, $resource['id']);
        $this->assertSame('Saya', $resource['first_name']);
        $this->assertSame('Tanaka', $resource['last_name']);
        $this->assertSame('Blue Tower', $resource['building']);
        $this->assertSame('Support', $resource['category']['content']);
    }
}

