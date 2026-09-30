<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('offline_stock_conflict_resolutions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('transaction_id');
            $table->string('action', 30);
            $table->unsignedInteger('source_location_id')->nullable();
            $table->unsignedInteger('adjustment_transaction_id')->nullable();
            $table->unsignedInteger('credit_note_transaction_id')->nullable();
            $table->unsignedInteger('resolved_by');
            $table->text('details')->nullable();
            $table->timestamps();
            $table->unique('transaction_id');
            $table->index(['business_id', 'action']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('offline_stock_conflict_resolutions');
    }
};
