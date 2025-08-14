<?php

namespace Tests\Feature;

use App\Models\Advertising;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_view_advertisements_list(): void
    {
        $user = User::factory()->create();
        $advertisement = Advertising::factory()->create();

        $response = $this->actingAs($user)
            ->get('/admin/advertisements');

        $response->assertStatus(200);
        $response->assertSee($advertisement->company_name);
    }

    public function test_admin_can_create_advertisement(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('advertisement.jpg');

        $response = $this->actingAs($user)
            ->post('/admin/advertisements', [
                'company_name' => 'Test Company',
                'phone_number' => '+964 750 123 4567',
                'image' => $file,
                'is_active' => true,
            ]);

        $response->assertRedirect('/admin/advertisements');
        $this->assertDatabaseHas('advertisements', [
            'company_name' => 'Test Company',
            'phone_number' => '+964 750 123 4567',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_advertisement(): void
    {
        $user = User::factory()->create();
        $advertisement = Advertising::factory()->create();

        $response = $this->actingAs($user)
            ->put("/admin/advertisements/{$advertisement->id}", [
                'company_name' => 'Updated Company',
                'phone_number' => '+964 750 987 6543',
                'image' => $advertisement->image,
                'is_active' => false,
            ]);

        $response->assertRedirect('/admin/advertisements');
        $this->assertDatabaseHas('advertisements', [
            'id' => $advertisement->id,
            'company_name' => 'Updated Company',
            'phone_number' => '+964 750 987 6543',
            'is_active' => false,
        ]);
    }

    public function test_admin_can_delete_advertisement(): void
    {
        $user = User::factory()->create();
        $advertisement = Advertising::factory()->create();

        $response = $this->actingAs($user)
            ->delete("/admin/advertisements/{$advertisement->id}");

        $response->assertRedirect('/admin/advertisements');
        $this->assertDatabaseMissing('advertisements', [
            'id' => $advertisement->id,
        ]);
    }

    public function test_advertisement_validation(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post('/admin/advertisements', [
                'company_name' => '',
                'phone_number' => '',
                'image' => '',
            ]);

        $response->assertSessionHasErrors(['company_name', 'phone_number', 'image']);
    }
}
