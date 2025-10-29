<?php

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AnimatedImageUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->afterStateUpdated(function ($state, $component) {
            if (!$state) {
                return;
            }

            $disk = $component->getDisk();
            // Ensure $disk is a string, not an array
            $disk = is_array($disk) ? ($disk[0] ?? 'public') : ($disk ?? 'public');
            $directory = $component->getDirectory();
            
            // Get the file path
            $filePath = is_array($state) ? $state[0] : $state;
            $fullPath = $directory ? $directory . '/' . $filePath : $filePath;
            
            // Check if it's a GIF file
            if (Storage::disk($disk)->exists($fullPath)) {
                $mimeType = Storage::disk($disk)->mimeType($fullPath);
                
                if ($mimeType === 'image/gif') {
                    // For GIF files, we don't process them to preserve animation
                    // Just ensure they're stored correctly
                    return;
                }
            }
            
            // For non-GIF files, apply the standard image processing
            $this->processImage($state, $component);
        });
    }

    protected function processImage($state, $component): void
    {
        if (!$state) {
            return;
        }

        $disk = $component->getDisk();
        // Ensure $disk is a string, not an array
        $disk = is_array($disk) ? ($disk[0] ?? 'public') : ($disk ?? 'public');
        $directory = $component->getDirectory();
        
        $filePath = is_array($state) ? $state[0] : $state;
        $fullPath = $directory ? $directory . '/' . $filePath : $filePath;
        
        if (!Storage::disk($disk)->exists($fullPath)) {
            return;
        }

        $mimeType = Storage::disk($disk)->mimeType($fullPath);
        
        // Skip processing for GIF files to preserve animation
        if ($mimeType === 'image/gif') {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read(Storage::disk($disk)->get($fullPath));
            
            // Apply aspect ratio and resize for non-GIF images
            $image->cover(800, 450);
            
            // Save the processed image
            Storage::disk($disk)->put($fullPath, $image->encode());
        } catch (\Exception $e) {
            // If image processing fails, leave the original file
            \Log::warning('Image processing failed: ' . $e->getMessage());
        }
    }
}
