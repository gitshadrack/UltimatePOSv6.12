<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add index to contacts table if it doesn't exist
        try {
            Schema::table('contacts', function (Blueprint $table) {
                $table->index(['business_id', 'type'], 'contacts_business_type_index');
            });
        } catch (\Exception $e) {
            // Index might already exist, skip
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
        }

        // Add index to dispatch_damages table if it doesn't exist
        try {
            Schema::table('dispatch_damages', function (Blueprint $table) {
                $table->index(['business_id', 'location_id', 'dispatched_at'], 'dispatch_damages_business_location_date');
            });
        } catch (\Exception $e) {
            // Index might already exist, skip
            if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                throw $e;
            }
        }
    }

    /**
     * Check if an index exists on a table
     *
     * @param string $table
     * @param string $index
     * @return bool
     */
    protected function hasIndex(string $table, string $index): bool
    {
        try {
            $connection = Schema::getConnection();
            $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$index]);
            return count($indexes) > 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function down(): void
    {
        // Check if index exists before dropping
        if ($this->hasIndex('contacts', 'contacts_business_type_index')) {
            Schema::table('contacts', function (Blueprint $table) {
                $table->dropIndex('contacts_business_type_index');
            });
        }

        if ($this->hasIndex('dispatch_damages', 'dispatch_damages_business_location_date')) {
            Schema::table('dispatch_damages', function (Blueprint $table) {
                $table->dropIndex('dispatch_damages_business_location_date');
            });
        }
    }
};
