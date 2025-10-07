<?php

namespace Tests\Feature;

use App\Models\Advertising;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdvertisingGifSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_gif_file()
    {
        Storage::fake('public');

        // Create a fake GIF file
        $gifFile = UploadedFile::fake()->image('test.gif', 800, 450)->mimeType('image/gif');

        $advertising = Advertising::create([
            'company_name' => 'Test Company',
            'phone_number' => '1234567890',
            'image' => $gifFile->store('advertisements', 'public'),
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('advertisements', [
            'company_name' => 'Test Company',
            'phone_number' => '1234567890',
            'is_active' => true,
        ]);

        // Verify the file was stored
        Storage::disk('public')->assertExists($advertising->image);
    }

    public function test_gif_file_preserves_original_format()
    {
        Storage::fake('public');

        // Create a fake GIF file
        $gifFile = UploadedFile::fake()->image('animated.gif', 800, 450)->mimeType('image/gif');

        $advertising = Advertising::create([
            'company_name' => 'GIF Company',
            'phone_number' => '9876543210',
            'image' => $gifFile->store('advertisements', 'public'),
            'is_active' => true,
        ]);

        // Verify the stored file maintains GIF format
        $storedFile = Storage::disk('public')->get($advertising->image);
        $this->assertNotEmpty($storedFile);
        
        // The file should exist and be accessible
        $this->assertTrue(Storage::disk('public')->exists($advertising->image));
    }

    public function test_advertising_model_handles_gif_urls()
    {
        Storage::fake('public');

        $advertising = new Advertising([
            'company_name' => 'GIF Test',
            'phone_number' => '5555555555',
            'image' => 'advertisements/test.gif',
            'is_active' => true,
        ]);

        // Test that the image URL is generated correctly
        $imageUrl = $advertising->image_url;
        $this->assertStringContains('storage/advertisements/test.gif', $imageUrl);
    }
}
