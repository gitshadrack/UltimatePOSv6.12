<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Check for API token in Authorization header
        $authHeader = $request->header('Authorization');
        
        if (!$authHeader) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Missing Authorization header'
            ], 401);
        }

        // Validate Bearer token format
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid Authorization header format. Use: Bearer <token>'
            ], 401);
        }

        $token = $matches[1];

        // TODO: Validate token against database
        // For now, basic validation - implement with Passport or Sanctum
        if (empty($token) || strlen($token) < 10) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'Invalid API token'
            ], 401);
        }

        // Store token in request for later use
        $request->attributes->set('api_token', $token);

        return $next($request);
    }
}
