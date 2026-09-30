<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Superadmin\Http\Controllers\TenantMigrationService;

class TenantImport extends Command
{
    protected $signature = 'pos:tenant-import {archive : Full path to encrypted archive} {--commit : Write records after validation}';

    protected $description = 'Validate or import one business into a fresh database';

    public function handle(TenantMigrationService $migration): int
    {
        if (! app()->isDownForMaintenance()) {
            $this->error('Put the destination application in maintenance mode first (php artisan down).');

            return self::FAILURE;
        }
        $path = realpath((string) $this->argument('archive'));
        if ($path === false || ! is_file($path)) {
            $this->error('Archive not found.');

            return self::FAILURE;
        }
        $password = $this->secret('Archive passphrase');
        if (! is_string($password)) {
            $this->error('A passphrase is required.');

            return self::FAILURE;
        }
        try {
            $preview = $migration->import($path, $password);
            $this->info('Preflight passed for business '.$preview['business_id'].' ('.count($preview['tables']).' tables).');
            if (! $this->option('commit')) {
                $this->line('No records were written. Repeat with --commit to import.');

                return self::SUCCESS;
            }
            if (! $this->confirm('Import into this fresh database now?', false)) {
                return self::SUCCESS;
            }
            $migration->import($path, $password, true);
            $this->info('Import completed. Verify transaction, payment, stock, and file totals before going live.');

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            $this->warn('If import started, discard this destination database and retry with a new empty one.');

            return self::FAILURE;
        }
    }
}
