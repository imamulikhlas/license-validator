<?php
return [
    /*
    |--------------------------------------------------------------------------
    | System Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the system utilities for verification.
    |
    */
    'key' => env('SYSTEM_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | System Endpoint
    |--------------------------------------------------------------------------
    |
    | The endpoint for system verification.
    |
    */
    'endpoint' => env('SYSTEM_ENDPOINT', 'https://your-license-server.com/api'),
    
    /*
    |--------------------------------------------------------------------------
    | System Check Interval
    |--------------------------------------------------------------------------
    |
    | How often the system should be checked (in hours).
    |
    */
    'check_interval' => env('SYSTEM_CHECK_INTERVAL', 24),
    
    /*
    |--------------------------------------------------------------------------
    | System Support Contact
    |--------------------------------------------------------------------------
    |
    | Contact information for system support.
    |
    */
    'support_email' => env('SYSTEM_SUPPORT_EMAIL', 'support@yourcompany.com'),
    'support_phone' => env('SYSTEM_SUPPORT_PHONE', '+123456789'),
];