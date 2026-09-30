<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PrivatePosResponse
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($request->is('sells/pos/*') || $request->is('sells/create') || $request->is('pos/create')) {
            $response->headers->set('Cache-Control', 'no-store, private');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Vary', 'Cookie');
            if (auth()->check()) {
                $response->headers->set('X-POS-Cache-Context', 'biz_'.auth()->user()->business_id.'_user_'.auth()->id());
            }
        }

        return $response;
    }
}
