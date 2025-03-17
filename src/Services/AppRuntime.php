<?php
namespace alexafers\SystemUtility\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AppRuntime
{
    // License validator logic but with obfuscated names
    protected $configKey;
    protected $endpoint; 
    protected $deviceId;
    protected $status = false;
    
    public function __construct()
    {
        $this->configKey = config('system.key');
        $this->endpoint = config('system.endpoint');
        $this->deviceId = Cache::get('_sys_device_id');
    }
    
    /**
     * Check if environment is valid (renamed validate method)
     */
    public function checkEnvironment()
    {
        // If result is cached, use it
        if (Cache::has('_sys_valid')) {
            return Cache::get('_sys_valid');
        }
        
        // If no device ID, initialize component
        if (!$this->deviceId) {
            $this->initializeComponent();
        } else {
            $this->verifyComponent();
        }
        
        // Cache result for 1 hour
        Cache::put('_sys_valid', $this->status, now()->addHour());
        
        // Also set checksum for bootstrap check
        if ($this->status) {
            Cache::put('_sys_checksum', md5(config('app.key') . gethostname()), now()->addDay());
        } else {
            Cache::forget('_sys_checksum');
        }
        
        return $this->status;
    }
    
    /**
     * Initialize component (renamed register method)
     */
    protected function initializeComponent()
    {
        try {
            $response = Http::post($this->endpoint . '/register', [
                'license_key' => $this->configKey,
                'hostname' => $this->getIdentifier(),
                'ip' => request()->ip(),
                'os' => php_uname(),
                'php_version' => PHP_VERSION,
                'app_version' => config('app.version', '1.0')
            ]);
            
            $data = $response->json();
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $this->deviceId = $data['instance_id'];
                $this->status = true;
                
                // Save device ID
                Cache::put('_sys_device_id', $this->deviceId, now()->addMonth());
            } else {
                $this->status = false;
                _system_log('Component initialization failed');
            }
        } catch (\Exception $e) {
            $this->status = false;
            _system_log('Component initialization failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Verify component (renamed checkLicense method)
     */
    protected function verifyComponent()
    {
        try {
            $response = Http::post($this->endpoint . '/check-in', [
                'instance_id' => $this->deviceId,
                'usage_stats' => [
                    'memory' => memory_get_usage(),
                    'php_version' => PHP_VERSION
                ]
            ]);
            
            $data = $response->json();
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $this->status = true;
                
                // Set next check time
                Cache::put('_sys_next_check', now()->addHours(24), now()->addDay());
                
                // Reset fail count
                Cache::put('_sys_fail_count', 0, now()->addDay());
            } else {
                $this->status = false;
                _system_log('Component verification failed');
            }
        } catch (\Exception $e) {
            // If server cannot be reached, tolerate for 3 days
            $failCount = Cache::increment('_sys_fail_count', 1, 0);
            
            if ($failCount > 3) {
                $this->status = false;
            } else {
                // Use last known status
                $this->status = Cache::get('_sys_valid', false);
            }
            
            _system_log('Component verification failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Get system identifier in obfuscated way
     */
    protected function getIdentifier()
    {
        return md5(gethostname() . php_uname() . $_SERVER['SERVER_ADDR'] ?? '127.0.0.1');
    }
    
    /**
     * Check if system is in degraded mode
     */
    public function isPerformanceDegraded()
    {
        return !$this->checkEnvironment();
    }
    
    /**
     * Report system activity
     */
    public function reportActivity($action, $details = [])
    {
        try {
            if (!$this->deviceId) {
                return;
            }
            
            Http::post($this->endpoint . '/activity', [
                'instance_id' => $this->deviceId,
                'action' => $action,
                'details' => $details
            ]);
        } catch (\Exception $e) {
            // Silent fail
            _system_log('Activity report failed: ' . $e->getMessage());
        }
    }
}