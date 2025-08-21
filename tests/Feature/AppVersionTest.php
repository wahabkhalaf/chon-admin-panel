<?php

namespace Tests\Feature;

use App\Models\AppVersion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppVersionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user with admin privileges
        $this->adminUser = User::factory()->admin()->create();
    }

    public function test_check_for_updates_requires_valid_platform()
    {
        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'invalid',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_check_for_updates_requires_all_fields()
    {
        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'android',
        ]);

        $response->assertStatus(400)
            ->assertJsonValidationErrors(['current_version', 'current_build_number', 'app_version']);
    }

    public function test_check_for_updates_no_versions_available()
    {
        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'update_available' => false,
                'message' => 'No updates available',
            ]);
    }

    public function test_check_for_updates_app_up_to_date()
    {
        // Create a version with the same build number
        AppVersion::factory()->create([
            'platform' => 'android',
            'version' => '1.0.0',
            'build_number' => 1,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'update_available' => false,
                'message' => 'App is up to date',
            ]);
    }

    public function test_check_for_updates_update_available()
    {
        // Create a newer version
        AppVersion::factory()->create([
            'platform' => 'android',
            'version' => '1.1.0',
            'build_number' => 2,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'update_available' => true,
                'message' => 'Update available',
            ])
            ->assertJsonStructure([
                'data' => [
                    'latest_version',
                    'latest_build_number',
                    'current_version',
                    'current_build_number',
                    'is_force_update',
                    'app_store_url',
                    'release_notes',
                    'released_at',
                    'update_message',
                ]
            ]);
    }

    public function test_check_for_updates_force_update()
    {
        // Create a force update version
        AppVersion::factory()->create([
            'platform' => 'android',
            'version' => '2.0.0',
            'build_number' => 10,
            'is_force_update' => true,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/app-updates/check', [
            'platform' => 'android',
            'current_version' => '1.0.0',
            'current_build_number' => 1,
            'app_version' => '1.0.0',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'update_available' => true,
                'data' => [
                    'is_force_update' => true,
                ]
            ]);
    }

    public function test_admin_can_get_all_versions()
    {
        $this->actingAs($this->adminUser);

        // Create some test versions
        AppVersion::factory()->count(3)->create();

        $response = $this->getJson('/api/app-updates');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_admin_can_create_new_version()
    {
        $this->actingAs($this->adminUser);

        $versionData = [
            'platform' => 'android',
            'version' => '1.3.0',
            'build_number' => 4,
            'app_store_url' => 'https://play.google.com/store/apps/details?id=com.chon.app',
            'release_notes' => 'New features added',
            'is_force_update' => false,
            'is_active' => true,
        ];

        $response = $this->postJson('/api/app-updates', $versionData);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'message' => 'App version created successfully',
            ]);

        $this->assertDatabaseHas('app_versions', $versionData);
    }

    public function test_admin_cannot_create_duplicate_version()
    {
        $this->actingAs($this->adminUser);

        // Create initial version
        AppVersion::factory()->create([
            'platform' => 'android',
            'version' => '1.0.0',
        ]);

        // Try to create duplicate
        $response = $this->postJson('/api/app-updates', [
            'platform' => 'android',
            'version' => '1.0.0',
            'build_number' => 2,
        ]);

        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'message' => 'Version already exists for this platform',
            ]);
    }

    public function test_admin_can_update_version()
    {
        $this->actingAs($this->adminUser);

        $version = AppVersion::factory()->create();

        $response = $this->putJson("/api/app-updates/{$version->id}", [
            'release_notes' => 'Updated release notes',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'App version updated successfully',
            ]);

        $this->assertDatabaseHas('app_versions', [
            'id' => $version->id,
            'release_notes' => 'Updated release notes',
        ]);
    }

    public function test_admin_can_delete_version()
    {
        $this->actingAs($this->adminUser);

        $version = AppVersion::factory()->create();

        $response = $this->deleteJson("/api/app-updates/{$version->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'App version deleted successfully',
            ]);

        $this->assertDatabaseMissing('app_versions', ['id' => $version->id]);
    }

    public function test_admin_can_get_statistics()
    {
        $this->actingAs($this->adminUser);

        // Create some test versions
        AppVersion::factory()->ios()->count(2)->create();
        AppVersion::factory()->android()->count(3)->create();
        AppVersion::factory()->forceUpdate()->create();

        $response = $this->getJson('/api/app-updates/statistics');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total_versions' => 6,
                    'ios_versions' => 2,
                    'android_versions' => 3,
                    'force_updates' => 1,
                ]
            ]);
    }

    public function test_non_admin_cannot_access_admin_endpoints()
    {
        $regularUser = User::factory()->create(['role' => 'user']);
        $this->actingAs($regularUser);

        $response = $this->getJson('/api/app-updates');
        $response->assertStatus(403);

        $response = $this->postJson('/api/app-updates', []);
        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_cannot_access_admin_endpoints()
    {
        $response = $this->getJson('/api/app-updates');
        $response->assertStatus(401);

        $response = $this->postJson('/api/app-updates', []);
        $response->assertStatus(401);
    }

    public function test_app_version_model_scopes()
    {
        // Create active and inactive versions
        AppVersion::factory()->active()->count(3)->create();
        AppVersion::factory()->inactive()->count(2)->create();
        
        // Create platform-specific versions
        AppVersion::factory()->ios()->count(2)->create();
        AppVersion::factory()->android()->count(3)->create();

        $this->assertEquals(3, AppVersion::active()->count());
        $this->assertEquals(2, AppVersion::platform('ios')->count());
        $this->assertEquals(3, AppVersion::platform('android')->count());
    }

    public function test_app_version_model_methods()
    {
        $version = AppVersion::factory()->create([
            'build_number' => 10,
            'is_force_update' => true,
        ]);

        $this->assertTrue($version->isUpdateRequired(5));
        $this->assertFalse($version->isUpdateRequired(15));
        $this->assertTrue($version->isForceUpdate(5));
        $this->assertFalse($version->isForceUpdate(15));
    }
}
