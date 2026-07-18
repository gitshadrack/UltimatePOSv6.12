<?php

namespace Tests\Unit;

use App\DarajaSetting;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class DarajaReversalTest extends TestCase
{
    public function test_security_credential_is_encrypted_at_rest()
    {
        $setting = new DarajaSetting();
        $setting->security_credential = 'generated-security-credential';

        $this->assertNotSame('generated-security-credential', $setting->getAttributes()['security_credential']);
        $this->assertSame('generated-security-credential', $setting->security_credential);
    }

    public function test_reversal_routes_are_registered_with_expected_methods()
    {
        $request_route = Route::getRoutes()->getByName('daraja.reverse');
        $result_route = Route::getRoutes()->getByName('daraja.reversal_result');
        $timeout_route = Route::getRoutes()->getByName('daraja.reversal_timeout');

        $this->assertNotNull($request_route);
        $this->assertSame(['POST'], $request_route->methods());
        $this->assertSame('daraja/payments/{id}/reverse', $request_route->uri());

        $this->assertNotNull($result_route);
        $this->assertSame(['POST'], $result_route->methods());
        $this->assertSame('daraja/reversal/{setting}/result', $result_route->uri());

        $this->assertNotNull($timeout_route);
        $this->assertSame(['POST'], $timeout_route->methods());
        $this->assertSame('daraja/reversal/{setting}/timeout', $timeout_route->uri());
    }
}
