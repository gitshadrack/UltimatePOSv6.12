<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class SystemDownloadSafetyTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_print_server_download_is_an_exact_whitelisted_repository_file(): void
    {
        $controller = file_get_contents($this->projectRoot.'/app/Http/Controllers/SystemDownloadController.php');

        $this->assertStringContainsString(
            "private const PRINT_SERVER_FILE = 'tools/windows-print-server/artifacts/UltimatePOS-PrintServer-Setup.exe';",
            $controller
        );
        $this->assertStringNotContainsString('$fileName', $controller);
        $this->assertStringContainsString("abort_unless(is_file(\$path), 404", $controller);
        $this->assertStringContainsString("'X-Content-Type-Options' => 'nosniff'", $controller);
    }

    public function test_download_routes_are_inside_the_authenticated_route_group(): void
    {
        $routes = file_get_contents($this->projectRoot.'/routes/web.php');
        $authGroup = strpos($routes, "Route::middleware(['setData', 'auth'");
        $indexRoute = strpos($routes, "Route::get('/downloads'");
        $downloadRoute = strpos($routes, "Route::get('/downloads/ultimatepos-print-server'");

        $this->assertNotFalse($authGroup);
        $this->assertGreaterThan($authGroup, $indexRoute);
        $this->assertGreaterThan($authGroup, $downloadRoute);
    }

    public function test_repository_keeps_only_the_setup_executable_from_generated_artifacts(): void
    {
        $gitignore = file_get_contents($this->projectRoot.'/.gitignore');

        $this->assertStringContainsString('/tools/windows-print-server/artifacts/*', $gitignore);
        $this->assertStringContainsString(
            '!/tools/windows-print-server/artifacts/UltimatePOS-PrintServer-Setup.exe',
            $gitignore
        );
    }

    public function test_downloads_are_linked_from_business_settings_system_section(): void
    {
        $systemSettings = file_get_contents(
            $this->projectRoot.'/resources/views/business/partials/settings_system.blade.php'
        );
        $sidebar = file_get_contents($this->projectRoot.'/app/Http/Middleware/AdminSidebarMenu.php');

        $this->assertStringContainsString("route('system-downloads.index')", $systemSettings);
        $this->assertStringContainsString("@lang('lang_v1.system_downloads')", $systemSettings);
        $this->assertStringNotContainsString('SystemDownloadController::class', $sidebar);
    }
}
