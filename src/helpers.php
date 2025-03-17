<?php
// Helper functions
if (!function_exists('system_valid')) {
    /**
     * Check if system is in valid state.
     *
     * @return bool
     */
    function system_valid()
    {
        if (app()->bound('system.runtime')) {
            return app('system.runtime')->checkEnvironment();
        }
        
        return true;
    }
}

if (!function_exists('_system_log')) {
    /**
     * Log system message in obfuscated way.
     *
     * @param string $message
     * @param array $context
     * @return void
     */
    function _system_log($message, $context = [])
    {
        try {
            logger()->debug('System: ' . $message, $context);
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}