<?php

namespace Modules\Superadmin\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use ZipArchive;

/**
 * Tenant archive for migration to a schema-matched, otherwise empty installation.
 * Primary keys are preserved; an existing business cannot be merged into.
 */
class TenantMigrationService
{
    private const FORMAT = 1;

    // Legacy tables without declared foreign keys.
    private const LINKS = [
        'account_transactions' => ['account_id' => 'accounts', 'transaction_id' => 'transactions', 'transaction_payment_id' => 'transaction_payments', 'transfer_transaction_id' => 'account_transactions'],
        'accounting_accounts_transactions' => ['accounting_account_id' => 'accounting_accounts', 'transaction_id' => 'transactions', 'transaction_payment_id' => 'transaction_payments', 'acc_trans_mapping_id' => 'accounting_acc_trans_mappings'],
        'accounting_budgets' => ['accounting_account_id' => 'accounting_accounts'],
        'categorizables' => ['category_id' => 'categories'],
        'discount_variations' => ['discount_id' => 'discounts', 'variation_id' => 'variations'],
        'essentials_document_shares' => ['document_id' => 'essentials_documents'],
        'hms_booking_extras' => ['transaction_id' => 'transactions', 'hms_extra_id' => 'hms_extras'],
        'hms_booking_lines' => ['transaction_id' => 'transactions', 'hms_room_id' => 'hms_rooms'],
        'hms_room_type_pricings' => ['hms_room_type_id' => 'hms_room_types'],
        'hms_room_unavailables' => ['hms_rooms_id' => 'hms_rooms'],
        'hms_rooms' => ['hms_room_type_id' => 'hms_room_types'],
        'journal_entries' => ['chart_of_account_id' => 'chart_of_accounts', 'location_id' => 'business_locations', 'contact_id' => 'contacts', 'created_by_id' => 'users', 'currency_id' => 'currencies'],
        'payment_details' => ['created_by_id' => 'users', 'payment_type_id' => 'payment_types'],
        'product_locations' => ['product_id' => 'products', 'location_id' => 'business_locations'],
        'sell_line_warranties' => ['sell_line_id' => 'transaction_sell_lines', 'warranty_id' => 'warranties'],
        'transaction_sell_lines_purchase_lines' => ['sell_line_id' => 'transaction_sell_lines', 'purchase_line_id' => 'purchase_lines', 'stock_adjustment_line_id' => 'stock_adjustment_lines'],
        'transfers' => ['transfer_from_id' => 'accounts', 'transfer_to_id' => 'accounts'],
        'user_contact_access' => ['user_id' => 'users', 'contact_id' => 'contacts'],
    ];

    public function export(int $businessId, string $path, string $password): array
    {
        $this->assertSupported();
        $this->assertPassword($password);
        if (! DB::table('business')->where('id', $businessId)->exists()) {
            throw new RuntimeException('Business not found.');
        }
        $tables = $this->tables();
        $links = $this->links();
        $rows = $this->tenantRows($businessId, $tables, $links);
        $this->assertSourceReferences($rows, $links);
        $manifest = [
            'format' => self::FORMAT,
            'business_id' => $businessId,
            'created_at' => now()->toIso8601String(),
            'schema_hash' => $this->schemaHash($tables),
            'tables' => [],
            'files' => [],
            'shared_references' => $this->sharedReferences($rows, $links),
        ];
        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create archive.');
        }
        try {
            foreach ($rows as $table => $data) {
                $json = json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
                $entry = "tables/{$table}.json";
                if (! $zip->addFromString($entry, $json) || ! $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $password)) {
                    throw new RuntimeException("Could not encrypt table: {$table}");
                }
                $manifest['tables'][$table] = ['count' => count($data), 'sha256' => hash('sha256', $json)];
            }
            $this->archiveFiles($zip, $rows, $manifest, $password);
            if (! $zip->addFromString('manifest.json', json_encode($manifest, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)) ||
                ! $zip->setEncryptionName('manifest.json', ZipArchive::EM_AES_256, $password)) {
                throw new RuntimeException('Could not encrypt manifest.');
            }
        } finally {
            if (! $zip->close()) {
                throw new RuntimeException('Archive could not be finalized.');
            }
        }

        return $manifest;
    }

    public function import(string $path, string $password, bool $commit = false): array
    {
        $this->assertSupported();
        $this->assertPassword($password);
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('Could not open archive.');
        }
        try {
            $zip->setPassword($password);
            $manifest = json_decode($zip->getFromName('manifest.json') ?: '', true, 512, JSON_THROW_ON_ERROR);
            if (($manifest['format'] ?? null) !== self::FORMAT || ! is_array($manifest['tables'] ?? null)) {
                throw new RuntimeException('Unsupported archive format.');
            }
            $tables = $this->tables();
            if (! hash_equals($this->schemaHash($tables), (string) ($manifest['schema_hash'] ?? ''))) {
                throw new RuntimeException('Destination schema differs from export. Match app version and enabled modules.');
            }
            $rows = [];
            foreach ($manifest['tables'] as $table => $info) {
                if (! in_array($table, $tables, true) || ! preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                    throw new RuntimeException('Archive references an unknown table.');
                }
                $json = $zip->getFromName("tables/{$table}.json");
                if ($json === false || ! hash_equals((string) $info['sha256'], hash('sha256', $json))) {
                    throw new RuntimeException("Table checksum failed: {$table}");
                }
                $rows[$table] = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
                if (! is_array($rows[$table]) || count($rows[$table]) !== (int) $info['count']) {
                    throw new RuntimeException("Table count failed: {$table}");
                }
            }
            if (count($rows['business'] ?? []) !== 1 || (int) $rows['business'][0]['id'] !== (int) $manifest['business_id']) {
                throw new RuntimeException('Business identity check failed.');
            }
            if (DB::table('business')->exists()) {
                throw new RuntimeException('Import requires a destination with no businesses.');
            }
            $this->assertNoCollisions($rows);
            $this->assertDestinationReferences($rows, $this->links());
            $this->verifySharedReferences($manifest);
            $this->verifyFiles($zip, $manifest, $rows);
            $order = $this->order($rows, $this->links());
            if (! $commit) {
                return ['status' => 'ready', 'business_id' => $manifest['business_id'], 'tables' => $manifest['tables']];
            }
            // MySQL's FK checks cannot be deferred. Some legacy tables are MyISAM,
            // so a failed destination must be discarded even after DB::rollBack().
            DB::beginTransaction();
            $restoredFiles = [];
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
                foreach ($order as $table) {
                    foreach (array_chunk($rows[$table], 200) as $chunk) {
                        DB::table($table)->insert($chunk);
                    }
                    if (DB::table($table)->count() !== count($rows[$table])) {
                        throw new RuntimeException("Imported row count differs for {$table}");
                    }
                    $this->verifyImportedRows($table, $rows[$table]);
                }
                $this->assertDestinationReferences($rows, $this->links());
                $this->restoreFiles($zip, $manifest, $restoredFiles);
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
                foreach ($restoredFiles as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                throw $e;
            }

            return ['status' => 'imported', 'business_id' => $manifest['business_id'], 'tables' => $manifest['tables']];
        } finally {
            $zip->close();
        }
    }

    private function assertSupported(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! class_exists(ZipArchive::class)) {
            throw new RuntimeException('Tenant migration requires MySQL and PHP ZIP.');
        }
    }

    private function assertPassword(string $password): void
    {
        if (strlen($password) < 12) {
            throw new RuntimeException('Use an archive passphrase of at least 12 characters.');
        }
    }

    private function tables(): array
    {
        return array_column(DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"), 'TABLE_NAME');
    }

    private function links(): array
    {
        $links = [];
        foreach (DB::select('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL') as $fk) {
            $links[$fk->TABLE_NAME][$fk->COLUMN_NAME] = [$fk->REFERENCED_TABLE_NAME, $fk->REFERENCED_COLUMN_NAME];
        }
        foreach (self::LINKS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                continue;
            }
            foreach ($columns as $column => $parent) {
                if (Schema::hasColumn($table, $column) && Schema::hasTable($parent)) {
                    $links[$table][$column] = [$parent, 'id'];
                }
            }
        }

        return $links;
    }

    private function tenantRows(int $businessId, array $tables, array $links): array
    {
        $rows = ['business' => DB::table('business')->where('id', $businessId)->get()->map(fn ($row) => (array) $row)->all()];
        foreach ($tables as $table) {
            if ($table !== 'business' && Schema::hasColumn($table, 'business_id')) {
                $rows[$table] = DB::table($table)->where('business_id', $businessId)->get()->map(fn ($row) => (array) $row)->all();
            }
        }
        $userIds = array_column($rows['users'] ?? [], 'id');
        foreach (['model_has_permissions', 'notifications'] as $table) {
            if (in_array($table, $tables, true) && $userIds) {
                $idColumn = $table === 'notifications' ? 'notifiable_id' : 'model_id';
                $typeColumn = $table === 'notifications' ? 'notifiable_type' : 'model_type';
                $rows[$table] = DB::table($table)->where($typeColumn, 'App\\User')->whereIn($idColumn, $userIds)->get()->map(fn ($row) => (array) $row)->all();
            }
        }
        do {
            $changed = false;
            foreach ($links as $table => $columns) {
                if (! in_array($table, $tables, true) || Schema::hasColumn($table, 'business_id')) {
                    continue;
                }
                foreach ($columns as $column => [$parent, $parentColumn]) {
                    if (empty($rows[$parent])) {
                        continue;
                    }
                    $ids = array_values(array_unique(array_filter(array_column($rows[$parent], $parentColumn), fn ($id) => $id !== null)));
                    $known = [];
                    foreach ($rows[$table] ?? [] as $row) {
                        $known[json_encode($row)] = true;
                    }
                    foreach (array_chunk($ids, 500) as $chunk) {
                        foreach (DB::table($table)->whereIn($column, $chunk)->get() as $result) {
                            $row = (array) $result;
                            $key = json_encode($row);
                            if (! isset($known[$key])) {
                                $rows[$table][] = $row;
                                $known[$key] = true;
                                $changed = true;
                            }
                        }
                    }
                }
            }
        } while ($changed);
        if (isset($rows['model_has_roles'])) {
            $rows['model_has_roles'] = array_values(array_filter(
                $rows['model_has_roles'],
                fn ($row) => $row['model_type'] === 'App\\User' && in_array($row['model_id'], $userIds, true)
            ));
        }
        if (! empty($rows['journal_entries']) && in_array('payment_details', $tables, true)) {
            $ids = array_values(array_filter(array_column($rows['journal_entries'], 'payment_detail_id')));
            foreach (array_chunk($ids, 500) as $chunk) {
                foreach (DB::table('payment_details')->whereIn('id', $chunk)->get() as $payment) {
                    $row = (array) $payment;
                    if (! collect($rows['payment_details'] ?? [])->contains('id', $row['id'])) {
                        $rows['payment_details'][] = $row;
                    }
                }
            }
        }
        ksort($rows);

        return $rows;
    }

    private function schemaHash(array $tables): string
    {
        $shape = [
            'tables' => DB::select("SELECT TABLE_NAME, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME"),
            'columns' => DB::select('SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA, CHARACTER_SET_NAME, COLLATION_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION'),
            'keys' => DB::select('SELECT TABLE_NAME, COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL ORDER BY TABLE_NAME, CONSTRAINT_NAME, ORDINAL_POSITION'),
        ];

        return hash('sha256', json_encode($shape, JSON_THROW_ON_ERROR));
    }

    private function assertSourceReferences(array $rows, array $links): void
    {
        foreach ($rows as $table => $data) {
            foreach ($links[$table] ?? [] as $column => [$parent, $parentColumn]) {
                foreach ($data as $row) {
                    $id = $row[$column] ?? null;
                    if ($id !== null && isset($rows[$parent]) && ! collect($rows[$parent])->contains($parentColumn, $id)) {
                        throw new RuntimeException("Cross-tenant or unselected reference: {$table}.{$column} -> {$parent}.{$parentColumn} ({$id})");
                    }
                }
            }
        }
    }

    private function assertNoCollisions(array $rows): void
    {
        foreach ($rows as $table => $data) {
            if (! $data) {
                continue;
            }
            if (DB::table($table)->exists()) {
                throw new RuntimeException("Destination table is not empty: {$table}");
            }
            if (! Schema::hasColumn($table, 'id')) {
                continue;
            }
            foreach (array_chunk(array_column($data, 'id'), 500) as $ids) {
                if (DB::table($table)->whereIn('id', $ids)->exists()) {
                    throw new RuntimeException("Destination ID collision in {$table}");
                }
            }
        }
    }

    private function verifyImportedRows(string $table, array $data): void
    {
        if (! $data || ! Schema::hasColumn($table, 'id')) {
            return;
        }
        $actual = [];
        foreach (array_chunk(array_column($data, 'id'), 500) as $ids) {
            foreach (DB::table($table)->whereIn('id', $ids)->get() as $row) {
                $actual[] = (array) $row;
            }
        }
        $sort = fn (&$set) => usort($set, fn ($a, $b) => $a['id'] <=> $b['id']);
        $sort($data);
        $sort($actual);
        if (json_encode($data, JSON_THROW_ON_ERROR) !== json_encode($actual, JSON_THROW_ON_ERROR)) {
            throw new RuntimeException("Imported row content differs for {$table}");
        }
    }

    private function assertDestinationReferences(array $rows, array $links): void
    {
        foreach ($rows as $table => $data) {
            foreach ($links[$table] ?? [] as $column => [$parent, $parentColumn]) {
                foreach ($data as $row) {
                    $id = $row[$column] ?? null;
                    if ($id === null || collect($rows[$parent] ?? [])->contains($parentColumn, $id)) {
                        continue;
                    }
                    if (! DB::table($parent)->where($parentColumn, $id)->exists()) {
                        throw new RuntimeException("Missing destination reference: {$table}.{$column} -> {$parent}.{$parentColumn} ({$id})");
                    }
                }
            }
        }
    }

    private function sharedReferences(array $rows, array $links): array
    {
        $references = [];
        foreach ($rows as $table => $data) {
            foreach ($links[$table] ?? [] as $column => [$parent, $parentColumn]) {
                if (array_key_exists($parent, $rows)) {
                    continue;
                }
                foreach ($data as $row) {
                    $id = $row[$column] ?? null;
                    if ($id === null) {
                        continue;
                    }
                    $key = $parent.'.'.$parentColumn.'.'.$id;
                    if (! isset($references[$key])) {
                        $shared = DB::table($parent)->where($parentColumn, $id)->first();
                        if (! $shared) {
                            throw new RuntimeException("Missing shared source reference: {$key}");
                        }
                        $references[$key] = hash('sha256', json_encode((array) $shared, JSON_THROW_ON_ERROR));
                    }
                }
            }
        }
        ksort($references);

        return $references;
    }

    private function verifySharedReferences(array $manifest): void
    {
        foreach ($manifest['shared_references'] ?? [] as $key => $expected) {
            $parts = explode('.', $key, 3);
            if (count($parts) !== 3 || ! preg_match('/^[a-zA-Z0-9_]+$/', $parts[0]) ||
                ! preg_match('/^[a-zA-Z0-9_]+$/', $parts[1])) {
                throw new RuntimeException('Invalid shared reference in archive.');
            }
            [$table, $column, $id] = $parts;
            $row = DB::table($table)->where($column, $id)->first();
            if (! $row || ! hash_equals($expected, hash('sha256', json_encode((array) $row, JSON_THROW_ON_ERROR)))) {
                throw new RuntimeException("Shared destination record differs: {$key}");
            }
        }
    }

    private function order(array $rows, array $links): array
    {
        // FK validation is explicit above; sorting improves diagnostics and locality.
        $tables = array_keys($rows);
        usort($tables, fn ($a, $b) => ($a === 'business' ? -1 : ($b === 'business' ? 1 : strcmp($a, $b))));

        return $tables;
    }

    private function fileReferences(array $rows): array
    {
        $map = [
            'business' => ['logo' => 'business_logos', 'login_image' => 'business_login_images', 'invoice_logo' => 'invoice_logos'],
            'invoice_layouts' => ['logo' => 'invoice_logos', 'letter_head' => 'invoice_logos'],
            'products' => ['image' => 'img'],
            'transactions' => ['document' => 'documents'],
            'transaction_payments' => ['document' => 'documents'],
            'media' => ['file_name' => 'media'],
        ];
        $files = [];
        foreach ($map as $table => $columns) {
            foreach ($rows[$table] ?? [] as $row) {
                foreach ($columns as $column => $directory) {
                    $name = $row[$column] ?? null;
                    if (! is_string($name) || $name === '') {
                        continue;
                    }
                    if (basename($name) !== $name || str_contains($name, '..') || str_contains($name, '\\')) {
                        throw new RuntimeException("Unsafe upload name in {$table}.{$column}");
                    }
                    $files['uploads/'.$directory.'/'.$name] = true;
                }
            }
        }

        return array_keys($files);
    }

    private function archiveFiles(ZipArchive $zip, array $rows, array &$manifest, string $password): void
    {
        foreach ($this->fileReferences($rows) as $entry) {
            $path = public_path($entry);
            if (! is_file($path)) {
                throw new RuntimeException("Referenced upload is missing: {$entry}");
            }
            if (! $zip->addFile($path, $entry) || ! $zip->setEncryptionName($entry, ZipArchive::EM_AES_256, $password)) {
                throw new RuntimeException("Could not archive upload: {$entry}");
            }
            $manifest['files'][$entry] = hash_file('sha256', $path);
        }
    }

    private function verifyFiles(ZipArchive $zip, array $manifest, array $rows): void
    {
        $expected = $this->fileReferences($rows);
        $listed = array_keys($manifest['files'] ?? []);
        sort($expected);
        sort($listed);
        if ($expected !== $listed) {
            throw new RuntimeException('Archive upload list does not match database references.');
        }
        foreach ($manifest['files'] ?? [] as $entry => $sha) {
            if (! str_starts_with($entry, 'uploads/') || str_contains($entry, '..') || str_contains($entry, '\\')) {
                throw new RuntimeException('Unsafe upload path in archive.');
            }
            $stream = $zip->getStream($entry);
            if ($stream === false) {
                throw new RuntimeException("Missing archive upload: {$entry}");
            }
            $hash = hash_init('sha256');
            hash_update_stream($hash, $stream);
            fclose($stream);
            if (! hash_equals($sha, hash_final($hash)) || file_exists(public_path($entry))) {
                throw new RuntimeException("Upload checksum or destination collision: {$entry}");
            }
        }
    }

    private function restoreFiles(ZipArchive $zip, array $manifest, array &$restoredFiles): void
    {
        foreach ($manifest['files'] ?? [] as $entry => $sha) {
            $target = public_path($entry);
            if (! is_dir(dirname($target)) && ! mkdir(dirname($target), 0750, true)) {
                throw new RuntimeException("Could not create upload directory: {$entry}");
            }
            $input = $zip->getStream($entry);
            $output = fopen($target, 'xb');
            if ($input === false || $output === false) {
                throw new RuntimeException("Could not restore upload: {$entry}");
            }
            $restoredFiles[] = $target;
            $copied = stream_copy_to_stream($input, $output);
            fclose($input);
            fclose($output);
            if ($copied === false || ! hash_equals($sha, hash_file('sha256', $target))) {
                throw new RuntimeException("Restored upload checksum failed: {$entry}");
            }
        }
    }
}
