<?php
// vendor/alexafers/license-validator/src/resources/config/system.php

return [
    // Konfigurasi minimal
    'key' => env('SYSTEM_KEY', ''),
    'endpoint' => env('SYSTEM_ENDPOINT', ''),
    
    // Dinonaktifkan secara default untuk development
    'enabled' => env('SYSTEM_ENABLED', false),
    
    // Fallback ke valid jika tidak bisa periksa
    'fail_open' => env('SYSTEM_FAIL_OPEN', true),
];