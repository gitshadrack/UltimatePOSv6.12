<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class VerifyCsrfToken extends BaseVerifier
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        // Installation endpoints - REMOVE these after deployment
        '/install/details',
        '/install/post-details',
        '/install/install-alternate',
        
        // Webhook endpoints - should be validated via signature verification instead
        '/webhook/*',
    ];
}
