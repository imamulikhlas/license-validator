<?php
// vendor/alexafers/license-validator/src/Services/AppRuntime.php
namespace alexafers\SystemUtility\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AppRuntime
{
    protected $configKey;
    protected $endpoint; 
    protected $deviceId;
    protected $status = false;
    
    // Guard terhadap rekursi
    private $isChecking = false;
    
    public function __construct()
    {
        $this->configKey = config('system.key');
        $this->endpoint = config('system.endpoint');
        $this->deviceId = Cache::get('_sys_device_id');
    }
    
    /**
     * Check environment with guard against recursion
     */
    public function checkEnvironment()
    {
        // Guard terhadap rekursi
        if ($this->isChecking) {
            return Cache::get('_sys_valid', false);
        }
        
        // Set flag
        $this->isChecking = true;
        
        try {
            // If result is cached, use it
            if (Cache::has('_sys_valid')) {
                $this->status = Cache::get('_sys_valid');
                return $this->status;
            }
            
            // Simple validation logic
            if (!$this->deviceId) {
                $this->initializeDevice();
            } else {
                $this->verifyDevice();
            }
            
            // Cache result untuk sementara
            Cache::put('_sys_valid', $this->status, now()->addHour());
            
            return $this->status;
        } finally {
            // Always reset flag
            $this->isChecking = false;
        }
    }
    
    /**
     * Initialize device with simple approach
     */
    protected function initializeDevice()
    {
        try {
            // Hindari memulai HTTP request jika credential tidak ada
            if (empty($this->configKey) || empty($this->endpoint)) {
                $this->status = false;
                return;
            }
            
            $response = Http::timeout(5)->post($this->endpoint . '/register', [
                'license_key' => $this->configKey,
                'hostname' => gethostname(),
                'ip' => request()->ip(),
                'os' => php_uname()
            ]);
            
            $data = $response->json();
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $this->deviceId = $data['instance_id'];
                $this->status = true;
                
                // Save device ID dengan TTL yang panjang
                Cache::put('_sys_device_id', $this->deviceId, now()->addMonth());
            } else {
                $this->status = false;
            }
        } catch (\Exception $e) {
            // Fallback ke default jika gagal
            $this->status = false;
        }
    }
    
    /**
     * Verify device status without recursion
     */
    protected function verifyDevice()
    {
        try {
            // Hindari HTTP request jika tidak perlu
            $lastCheck = Cache::get('_sys_last_check', 0);
            $checkInterval = 86400; // 24 jam dalam detik
            
            // Periksa hanya jika interval telah berlalu
            if (time() - $lastCheck < $checkInterval) {
                // Gunakan status terakhir
                $this->status = Cache::get('_sys_valid', false);
                return;
            }
            
            // Buat request sederhana
            $response = Http::timeout(5)->post($this->endpoint . '/check-in', [
                'instance_id' => $this->deviceId
            ]);
            
            $data = $response->json();
            
            if (isset($data['status']) && $data['status'] === 'success') {
                $this->status = true;
                
                // Update last check time
                Cache::put('_sys_last_check', time(), now()->addWeek());
            } else {
                $this->status = false;
            }
        } catch (\Exception $e) {
            // Fallback ke cached value jika ada
            $this->status = Cache::get('_sys_valid', false);
        }
    }
}