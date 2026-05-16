# Ultimate POS - Critical Security Fixes (Quick Reference)

## 1️⃣ FIX CORS Configuration (5 minutes)

**File:** `config/cors.php`

Replace this:

```php
'allowed_methods' => ['*'],
'allowed_origins' => ['*'],
'allowed_headers' => ['*'],
```

With this:

```php
'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
'allowed_origins' => [
    env('APP_URL'),
    'https://yourdomain.com',
    'https://www.yourdomain.com',
],
'allowed_headers' => ['Content-Type', 'X-Requested-With', 'Authorization', 'X-CSRF-TOKEN'],
'max_age' => 3600,
'supports_credentials' => true,
```

---

## 2️⃣ Fix Password Validation (5 minutes)

**File:** `app/Http/Controllers/BusinessController.php`

Find:

```php
'password' => 'required|min:4|max:255',
```

Replace with:

```php
'password' => [
    'required',
    'min:12',
    'max:255',
    'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
    'confirmed',
],
'password_confirmation' => 'required',
```

---

## 3️⃣ Add Security Headers Middleware (10 minutes)

Create: `app/Http/Middleware/SecurityHeaders.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;

class SecurityHeaders
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $response->header('X-Frame-Options', 'DENY');
        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-XSS-Protection', '1; mode=block');
        $response->header('Content-Security-Policy', "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (\App::environment('production')) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
```

Register in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... existing middleware ...
    \App\Http\Middleware\SecurityHeaders::class,
];
```

---

## 4️⃣ Secure Session Configuration (5 minutes)

**File:** `.env`

Update:

```
SESSION_DRIVER=database
SESSION_LIFETIME=30
SESSION_ENCRYPT=true
```

**File:** `.env.production`

Ensure same values.

Create session table:

```bash
php artisan session:table
php artisan migrate
```

---

## 5️⃣ Restrict CSRF Exceptions (5 minutes)

**File:** `app/Http/Middleware/VerifyCsrfToken.php`

Update to:

```php
protected $except = [
    // Keep only install endpoints during initial setup
    // Remove after production deployment
];
```

For API endpoints, create middleware:

**File:** `app/Http/Middleware/ApiTokenVerify.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;

class ApiTokenVerify
{
    public function handle($request, Closure $next)
    {
        // Check for API token in Authorization header
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Validate token (use Passport or Sanctum)
        // This is just a basic example

        return $next($request);
    }
}
```

---

## 6️⃣ Add Rate Limiting (10 minutes)

**File:** `routes/api.php`

```php
Route::middleware('throttle:30:1')->group(function () {
    Route::post('/ecom/customers', [CustomerController::class, 'store']);
    Route::post('/ecom/orders', [OrderController::class, 'store']);
});

Route::middleware('throttle:5:1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
});
```

---

## 7️⃣ Protect Upload Directory (.htaccess)

**Create:** `public/uploads/.htaccess`

```apache
<FilesMatch "\.(php|php3|php4|php5|phtml|phps|pht|phar|ini)$">
    Deny from all
</FilesMatch>

Options -Indexes
AddType text/plain .php .phtml .php3 .php4 .php5 .phps .pht .phar .ini
```

---

## 8️⃣ Add .env to .gitignore (1 minute)

**File:** `.gitignore`

Add/Verify:

```
.env
.env.local
.env.*.php
.env.backup
.env.*.backup
```

Check git history:

```bash
# See if .env was ever committed
git log --all --full-history -- .env

# If yes, remove from history (dangerous, use with caution)
git filter-branch --tree-filter 'rm -f .env' -- --all
```

---

## 9️⃣ Rotate Database Password (Immediate)

1. Create new user:

```sql
CREATE USER 'posapp_prod'@'localhost' IDENTIFIED BY 'NewStrongPassword!@#2024';
GRANT ALL PRIVILEGES ON updatedb.* TO 'posapp_prod'@'localhost';
FLUSH PRIVILEGES;
```

2. Update `.env`:

```
DB_USERNAME=posapp_prod
DB_PASSWORD=NewStrongPassword!@#2024
```

3. Test connection:

```bash
php artisan tinker
>>> DB::connection()->getPdo()
```

4. Drop old user:

```sql
DROP USER 'root'@'localhost';
```

---

## 🔟 Add Input Validation Service (20 minutes)

Create: `app/Http/Requests/ValidateBusinessInput.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ValidateBusinessInput extends FormRequest
{
    public function authorize()
    {
        return auth()->check();
    }

    public function rules()
    {
        return [
            'name' => 'required|string|max:255|regex:/^[a-zA-Z0-9\s\-\.]+$/',
            'email' => 'required|email:rfc,dns',
            'password' => [
                'required',
                'min:12',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])/',
            ],
            'phone' => 'nullable|regex:/^[0-9\-\+\s\(\)]+$/',
            'website' => 'nullable|url',
        ];
    }

    public function messages()
    {
        return [
            'name.regex' => 'Name contains invalid characters.',
            'password.regex' => 'Password must have uppercase, lowercase, number, and special char.',
            'email.email' => 'Invalid email address.',
        ];
    }
}
```

Use in controller:

```php
public function store(ValidateBusinessInput $request)
{
    // All input is validated
    $business = Business::create($request->validated());
}
```

---

## Testing Commands

```bash
# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> DB::statement('SELECT 1')

# Verify .env not in git
git ls-files | grep .env

# Check security headers
curl -i https://yourdomain.com | grep -i "X-Frame-Options\|X-Content-Type\|Strict-Transport"

# Test CORS configuration
curl -H "Origin: https://attacker.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: Content-Type" \
     -X OPTIONS https://yourdomain.com/api/endpoint -v

# Check password requirements in code
grep -r "min:" app/Http/Controllers/ | grep password

# Verify rate limiting
for i in {1..35}; do curl https://yourdomain.com/api/login; done
# Should return 429 (Too Many Requests) after limit exceeded
```

---

## Deployment Checklist

- [ ] All `.env` values updated for production
- [ ] Database credentials rotated
- [ ] CORS configuration restricted
- [ ] Password validation updated
- [ ] Security headers middleware added
- [ ] Session driver configured to database
- [ ] Rate limiting implemented
- [ ] Input validation added
- [ ] `.env` removed from git history
- [ ] `.env` in `.gitignore`
- [ ] HTTPS enforced and certificate valid
- [ ] Error logging configured
- [ ] Security monitoring enabled
- [ ] Backups encrypted and tested
- [ ] Team trained on deployment process

---

## Emergency Contact

If you discover a security vulnerability:

1. DO NOT commit/push the finding
2. Document the issue privately
3. Notify all team members
4. Implement fix in isolated branch
5. Deploy after thorough testing

---

**Last Updated:** May 16, 2026  
**Next Review:** After implementing critical fixes  
**Approved By:** Security Team
