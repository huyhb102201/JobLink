<?php
return [
    'url'    => env('CLOUDINARY_URL'),
    'cloud'  => env('CLOUDINARY_CLOUD_NAME'),
    'key'    => env('CLOUDINARY_API_KEY'),
    'secret' => env('CLOUDINARY_API_SECRET'),
    'secure' => env('CLOUDINARY_SECURE', true),
];
