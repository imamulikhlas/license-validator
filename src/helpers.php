<?php
// vendor/alexafers/license-validator/src/helpers.php

// Hindari define function jika sudah ada
if (!function_exists('system_valid')) {
    /**
     * Check if system is in valid state - safe implementation
     */
    function system_valid()
    {
        try {
            // Guard against app() not being available
            if (!function_exists('app')) {
                return true;
            }
            
            // Guard against service not registered
            if (!app()->bound('system.runtime')) {
                return true;
            }
            
            // Get from cache if possible
            if (function_exists('cache') && cache()->has('_sys_valid')) {
                return cache()->get('_sys_valid');
            }
            
            return app('system.runtime')->checkEnvironment();
        } catch (\Exception $e) {
            // Failsafe return
            return true;
        }
    }
}

// Logger helper yang aman
if (!function_exists('_system_log')) {
    function _system_log($message, $context = [])
    {
        try {
            if (function_exists('logger')) {
                logger()->debug('System: ' . $message, $context);
            }
        } catch (\Exception $e) {
            // Silent fail
        }
    }
}