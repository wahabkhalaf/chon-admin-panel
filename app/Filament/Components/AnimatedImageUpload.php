<?php

namespace App\Filament\Components;

use Filament\Forms\Components\FileUpload;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AnimatedImageUpload extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();
        
        // Process files after they're stored by Filament
        $this->afterStateUpdated(function ($state, $component) {
            if (!$state) {
                return;
            }

            $disk = $component->getDisk(); // Returns Filesystem object
            $directory = $component->getDirectory();
            
            // Get the file path
            $filePath = is_array($state) ? $state[0] : $state;
            $fullPath = $directory ? $directory . '/' . $filePath : $filePath;
            
            // Check if it's a GIF file - if so, skip all processing
            if ($disk->exists($fullPath)) {
                $mimeType = $disk->mimeType($fullPath);
                
                if ($mimeType === 'image/gif') {
                    // GIF files are preserved as-is - no processing applied
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

        $disk = $component->getDisk(); // Returns Filesystem object
        $directory = $component->getDirectory();
        
        $filePath = is_array($state) ? $state[0] : $state;
        $fullPath = $directory ? $directory . '/' . $filePath : $filePath;
        
        if (!$disk->exists($fullPath)) {
            return;
        }

        $mimeType = $disk->mimeType($fullPath);
        
        // Skip processing for GIF files to preserve animation
        if ($mimeType === 'image/gif') {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $image = $manager->read($disk->get($fullPath));
            
            // Apply aspect ratio and resize for non-GIF images
            $image->cover(800, 450);
            
            // Save the processed image
            $disk->put($fullPath, $image->encode());
        } catch (\Exception $e) {
            // If image processing fails, leave the original file
            \Log::warning('Image processing failed: ' . $e->getMessage());
        }
    }
}
