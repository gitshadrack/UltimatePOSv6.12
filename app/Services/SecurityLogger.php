<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SecurityLogger
{
    /**
     * Log failed login attempt
     */
    public static function logFailedLogin($email, $reason = 'Invalid credentials')
    {
        Log::warning('Failed login attempt', [
            'email' => $email,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'reason' => $reason,
            'timestamp' => now(),
        ]);

        // TODO: Implement account lockout after N failed attempts
        static::checkBruteForceAttack($email);
    }

    /**
     * Log successful login
     */
    public static function logSuccessfulLogin($user_id, $username)
    {
        Log::info('Successful login', [
            'user_id' => $user_id,
            'username' => $username,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log unauthorized access attempt
     */
    public static function logUnauthorizedAccess($attempted_action, $resource_id = null)
    {
        $user_id = Auth::check() ? Auth::id() : 'Guest';
        
        Log::warning('Unauthorized access attempt', [
            'user_id' => $user_id,
            'action' => $attempted_action,
            'resource_id' => $resource_id,
            'ip' => request()->ip(),
            'url' => request()->url(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log API authentication failure
     */
    public static function logApiAuthFailure($token_prefix, $reason)
    {
        Log::warning('API authentication failure', [
            'token_prefix' => substr($token_prefix, 0, 10) . '***',
            'reason' => $reason,
            'ip' => request()->ip(),
            'endpoint' => request()->path(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log suspicious file upload
     */
    public static function logSuspiciousFileUpload($filename, $reason)
    {
        Log::warning('Suspicious file upload attempt', [
            'filename' => $filename,
            'reason' => $reason,
            'user_id' => Auth::check() ? Auth::id() : 'Guest',
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log password change
     */
    public static function logPasswordChange($user_id, $changed_by_user = true)
    {
        $log_type = $changed_by_user ? 'User password changed' : 'Admin password reset';
        
        Log::info($log_type, [
            'user_id' => $user_id,
            'changed_by' => $changed_by_user ? 'Self' : Auth::id(),
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log permission denied
     */
    public static function logPermissionDenied($resource, $action)
    {
        Log::warning('Permission denied', [
            'user_id' => Auth::check() ? Auth::id() : 'Guest',
            'resource' => $resource,
            'action' => $action,
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Check for brute force attack pattern
     */
    public static function checkBruteForceAttack($email, $attempts = 5, $minutes = 15)
    {
        // TODO: Implement Redis-based counter for brute force detection
        // For now, just log it
        $cache_key = "failed_login_{$email}";
        $attempts_count = cache($cache_key, 0) + 1;
        
        cache([$cache_key => $attempts_count], Carbon::now()->addMinutes($minutes));

        if ($attempts_count >= $attempts) {
            Log::alert('Possible brute force attack detected', [
                'email' => $email,
                'attempts' => $attempts_count,
                'ip' => request()->ip(),
                'time_window' => "{$minutes} minutes",
            ]);
        }
    }

    /**
     * Log database query errors
     */
    public static function logDatabaseError($error_message, $query = null)
    {
        Log::error('Database error', [
            'error' => $error_message,
            'query' => $query,
            'user_id' => Auth::check() ? Auth::id() : 'Guest',
            'ip' => request()->ip(),
            'timestamp' => now(),
        ]);
    }

    /**
     * Log rate limit exceeded
     */
    public static function logRateLimitExceeded($endpoint, $ip = null)
    {
        Log::warning('Rate limit exceeded', [
            'endpoint' => $endpoint,
            'ip' => $ip ?? request()->ip(),
            'user_id' => Auth::check() ? Auth::id() : 'Guest',
            'timestamp' => now(),
        ]);
    }
}
