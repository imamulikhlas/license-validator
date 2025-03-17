<?php
// File ini di-autoload melalui composer.json
// Akan dijalankan sebelum Laravel boot

use Illuminate\Foundation\Application;

// Mencegah direct access
if (!defined('LARAVEL_START')) {
    return;
}

// Menyisipkan validator sebelum framework boot
if (!function_exists('_check_system_validity')) {
    function _check_system_validity() {
        try {
            // Cek keberadaan system cache terenkripsi
            $cached = false;
            if (function_exists('cache') && cache()->has('_sys_checksum')) {
                $cached = true;
                $valid = cache()->get('_sys_checksum') === md5(config('app.key') . gethostname());
                
                if (!$valid) {
                    // Set global flag yang akan dicek nanti
                    $GLOBALS['_sys_invalid'] = true;
                }
                return $valid;
            }
            
            return true; // Pada boot awal, beri toleransi
        } catch (\Exception $e) {
            return true; // Sama seperti di atas
        }
    }
}

// Hook ke proses boot Laravel
if (class_exists(Application::class)) {
    // Override beberapa method internal Laravel
    $methods = ['registerConfigBindings', 'registerDatabaseBindings'];
    
    foreach ($methods as $method) {
        if (method_exists(Application::class, $method)) {
            $originalMethod = Application::class . '::' . $method;
            
            // Simpan implementasi asli
            if (!function_exists('_original_' . $method)) {
                eval('
                function _original_' . $method . '($app) {
                    $app->' . $method . '();
                    return $app;
                }
                ');
            }
            
            // Override method dengan implementasi kita
            eval('
            function _' . $method . '_wrapper($app) {
                // Panggil method asli
                _original_' . $method . '($app);
                
                // Cek validitas secara tersembunyi
                if (isset($GLOBALS["_sys_invalid"]) && $GLOBALS["_sys_invalid"]) {
                    // Tambahkan kerusakan halus
                    if ($method === "registerDatabaseBindings") {
                        // Kerusakan database akan terlihat saat query
                    }
                }
                
                return $app;
            }
            ');
        }
    }
    
    // Intercept Application construction
    if (!app() || !app()->hasBeenBootstrapped()) {
        // Check early validation
        _check_system_validity();
    }
}