<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Superadmin\Http\Controllers\TenantMigrationService;

class TenantExport extends Command
{
    protected $signature = 'pos:tenant-export {business_id : Source business ID}';

    protected $description = 'Export one business to an encrypted archive while the application is in maintenance mode';

    public function handle(TenantMigrationService $migration): int
    {
        if (! app()->isDownForMaintenance()) {
            $this->error('Put the source application in maintenance mode first (php artisan down). Stop offline clients and background workers too.');

            return self::FAILURE;
        }
        $password = $this->secret('Archive passphrase (at least 12 characters)');
        if (! is_string($password) || strlen($password) < 12) {
            $this->error('A passphrase of at least 12 characters is required.');

            return self::FAILURE;
        }
        $directory = storage_path('app/tenant-migrations');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true)) {
            $this->error('Could not create private archive directory.');

            return self::FAILURE;
        }
        $path = $directory.'/business-'.(int) $this->argument('business_id').'-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(4)).'.zip';
        try {
            $manifest = $migration->export((int) $this->argument('business_id'), $path, $password);
            $this->info('Encrypted archive: '.$path);
            $this->info('Tables: '.count($manifest['tables']).'; files: '.count($manifest['files']));
            $this->warn('Keep the source offline until the destination import and reconciliation are complete.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            if (is_file($path)) {
                unlink($path);
            }
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}
