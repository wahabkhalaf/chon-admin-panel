<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Temporary File Uploads
    |--------------------------------------------------------------------------
    |
    | Configure how Livewire handles temporary file uploads. This setting
    | controls the validation rules for file uploads before they are
    | processed and moved to their final location.
    |
    */

    'temporary_file_upload' => [
        'rules' => 'file|max:30720', // 30 MB (in KB) - matches server limit
        'directory' => null, // Default: storage_path('app/livewire-tmp')
        'disk' => null, // Default: config('filesystems.default')
        'middleware' => null,
    ],

];

