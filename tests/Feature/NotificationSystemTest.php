<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Services\ExpressApiClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_notification()
    {
        $notification = Notification::create([
            'title' => 'Test Notification',
            'message' => 'This is a test notification',
            'type' => 'general',
            'priority' => 'normal',
            'data' => ['test' => true],
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'title' => 'Test Notification',
            'status' => 'pending',
        ]);
    }

    public function test_notification_scopes_work()
    {
        // Create different types of notifications
        Notification::factory()->pending()->create();
        Notification::factory()->sent()->create();
        Notification::factory()->failed()->create();

        // Create a scheduled notification that's not ready to send (future time)
        Notification::factory()->create([
            'status' => 'pending',
            'scheduled_at' => now()->addMinutes(10),
            'sent_at' => null,
        ]);

        Notification::factory()->readyToSend()->create();

        $this->assertEquals(3, Notification::pending()->count()); // pending, scheduled, readyToSend
        $this->assertEquals(1, Notification::sent()->count());
        $this->assertEquals(1, Notification::failed()->count());
        $this->assertEquals(2, Notification::scheduled()->count()); // scheduled + readyToSend
        $this->assertEquals(1, Notification::readyToSend()->count());
    }

    public function test_notification_model_methods()
    {
        $notification = Notification::factory()->readyToSend()->create();

        $this->assertTrue($notification->isReadyToSend());

        $notification->markAsSent(['success' => true]);
        $this->assertEquals('sent', $notification->fresh()->status);
        $this->assertNotNull($notification->fresh()->sent_at);

        $notification->markAsFailed(['error' => 'test error']);
        $this->assertEquals('failed', $notification->fresh()->status);
    }

    public function test_express_api_client_configuration()
    {
        $apiClient = app(ExpressApiClient::class);

        // Test that the client can be instantiated
        $this->assertInstanceOf(ExpressApiClient::class, $apiClient);

        // Test connection method (will likely fail without actual API)
        $result = $apiClient->testConnection();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('success', $result);
    }

    public function test_notification_factory_states()
    {
        $pending = Notification::factory()->pending()->create();
        $this->assertEquals('pending', $pending->status);
        $this->assertNull($pending->sent_at);

        $sent = Notification::factory()->sent()->create();
        $this->assertEquals('sent', $sent->status);
        $this->assertNotNull($sent->sent_at);

        $failed = Notification::factory()->failed()->create();
        $this->assertEquals('failed', $failed->status);
        $this->assertNull($failed->sent_at);

        $scheduled = Notification::factory()->scheduled()->create();
        $this->assertEquals('pending', $scheduled->status);
        $this->assertNotNull($scheduled->scheduled_at);
        $this->assertTrue($scheduled->scheduled_at->isFuture());

        $readyToSend = Notification::factory()->readyToSend()->create();
        $this->assertEquals('pending', $readyToSend->status);
        $this->assertNotNull($readyToSend->scheduled_at);
        $this->assertTrue($readyToSend->scheduled_at->isPast());
    }

    public function test_notification_data_casting()
    {
        $data = ['key' => 'value', 'number' => 123];
        $notification = Notification::create([
            'title' => 'Test',
            'message' => 'Test message',
            'type' => 'general',
            'priority' => 'normal',
            'data' => $data,
            'status' => 'pending',
        ]);

        $this->assertIsArray($notification->data);
        $this->assertEquals($data, $notification->data);
        $this->assertEquals('value', $notification->data['key']);
    }
}
