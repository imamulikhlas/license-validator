<?php
namespace alexafers\SystemUtility\Http\Controllers;

use Illuminate\Routing\Controller;

class SystemController extends Controller
{
    /**
     * Show system notice page
     *
     * @return \Illuminate\View\View
     */
    public function notice()
    {
        return view('system-utility::system-notice');
    }
    
    /**
     * Handle system check API endpoint
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function check()
    {
        $isValid = app('system.runtime')->checkEnvironment();
        
        return response()->json([
            'status' => $isValid ? 'optimal' : 'needs_maintenance',
            'timestamp' => time()
        ]);
    }
}