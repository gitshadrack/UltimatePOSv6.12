<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PrinterReliabilityTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectRoot = dirname(__DIR__, 2);
    }

    public function test_browser_client_uses_the_local_print_server_with_visible_statuses(): void
    {
        $client = file_get_contents($this->projectRoot.'/public/js/printer.js');

        $this->assertStringContainsString("ws://127.0.0.1:6441", $client);
        $this->assertStringContainsString('function sendToPosPrintServer(content)', $client);
        $this->assertStringContainsString("setPrinterStatus('connecting'", $client);
        $this->assertStringContainsString("setPrinterStatus('ready'", $client);
        $this->assertStringContainsString("setPrinterStatus('error'", $client);
    }

    public function test_pos_printing_waits_for_the_websocket_instead_of_using_a_timer(): void
    {
        $posClient = file_get_contents($this->projectRoot.'/public/js/pos.js');
        $returnClient = file_get_contents($this->projectRoot.'/public/js/sell_return.js');

        $this->assertStringContainsString('sendToPosPrintServer(content)', $posClient);
        $this->assertStringContainsString('sendToPosPrintServer(content)', $returnClient);
        $this->assertStringNotContainsString('socket.send(JSON.stringify(content))', $posClient);
        $this->assertStringNotContainsString('socket.send(JSON.stringify(content))', $returnClient);
    }

    public function test_windows_deployment_contains_install_start_test_and_uninstall_workflows(): void
    {
        $toolDirectory = $this->projectRoot.'/tools/windows-print-server';

        foreach ([
            'Install.cmd',
            'Install-PrintServer.ps1',
            'Start-PrintServer.ps1',
            'Test-PrintServer.ps1',
            'Uninstall-PrintServer.ps1',
            'Build-DeploymentPackage.ps1',
            'runtime-php.ini',
            'README.md',
            'payload-overrides/server.php',
            'payload-overrides/queue-worker.php',
            'payload-overrides/lib/DurablePrintQueue.php',
        ] as $requiredFile) {
            $this->assertFileExists($toolDirectory.'/'.$requiredFile);
        }

        $installer = file_get_contents($toolDirectory.'/Install-PrintServer.ps1');
        $launcher = file_get_contents($toolDirectory.'/Start-PrintServer.ps1');
        $builder = file_get_contents($toolDirectory.'/Build-DeploymentPackage.ps1');

        $this->assertStringContainsString("New-ScheduledTaskTrigger -AtLogOn", $installer);
        $this->assertStringContainsString("'UltimatePOS Print Server'", $installer);
        $this->assertStringContainsString('queue-worker\.php', $installer);
        $this->assertStringContainsString('LocalPort 6441', $launcher);
        $this->assertStringContainsString('retrying in 5 seconds', $launcher);
        $this->assertStringContainsString('queue-worker.php', $launcher);
        $this->assertStringContainsString(
            '$compatibleSmbCharacterClass = \'[\s\d\w+-]\'',
            $builder
        );
        $this->assertStringContainsString(
            '$compatibleImplodeCall = \'implode("", $this -> buffer)\'',
            $builder
        );
        $this->assertStringContainsString(
            '$compatibleLineImplodeCall = \'implode("\n", $allLines)\'',
            $builder
        );
    }
}
