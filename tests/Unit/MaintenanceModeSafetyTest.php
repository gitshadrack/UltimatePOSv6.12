<?php

namespace Tests\Unit;

use App\Http\Middleware\EncryptCookies;
use Illuminate\Foundation\Exceptions\RegisterErrorViewPaths;
use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MaintenanceModeSafetyTest extends TestCase
{
    public function test_maintenance_bypass_cookie_is_not_encrypted(): void
    {
        $middleware = app(EncryptCookies::class);

        $this->assertTrue($middleware->isDisabled('laravel_maintenance'));
    }

    public function test_framework_accepts_the_generated_maintenance_cookie(): void
    {
        $secret = 'test-maintenance-secret';
        $cookie = MaintenanceModeBypassCookie::create($secret);

        $this->assertTrue(
            MaintenanceModeBypassCookie::isValid($cookie->getValue(), $secret)
        );
        $this->assertFalse(
            MaintenanceModeBypassCookie::isValid($cookie->getValue(), 'wrong-secret')
        );
    }

    public function test_maintenance_actions_are_post_only_and_superadmin_protected(): void
    {
        foreach ([
            'superadmin.maintenance.enable',
            'superadmin.maintenance.disable',
        ] as $route_name) {
            $route = Route::getRoutes()->getByName($route_name);

            $this->assertNotNull($route);
            $this->assertSame(['POST'], $route->methods());
            $this->assertContains('auth', $route->gatherMiddleware());
            $this->assertContains('superadmin', $route->gatherMiddleware());
        }
    }

    public function test_maintenance_page_can_be_prerendered_without_database_content(): void
    {
        (new RegisterErrorViewPaths)();

        $html = view('errors::503', ['retryAfter' => 60])->render();

        $this->assertStringContainsString('Scheduled Maintenance', $html);
        $this->assertStringContainsString('Suggested retry: 60 seconds', $html);
    }
}
