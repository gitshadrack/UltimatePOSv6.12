<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddFullDispatchStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // Alter the dispatch_status enum to add 'full_dispatch'
        DB::statement("ALTER TABLE damage_records MODIFY COLUMN dispatch_status ENUM('not_dispatched', 'partial', 'dispatched', 'full_dispatch') DEFAULT 'not_dispatched'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        // Revert back to original enum values
        DB::statement("ALTER TABLE damage_records MODIFY COLUMN dispatch_status ENUM('not_dispatched', 'partial', 'dispatched') DEFAULT 'not_dispatched'");
    }
}

