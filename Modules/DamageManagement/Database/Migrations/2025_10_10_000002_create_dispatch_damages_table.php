<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('dispatch_damages', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->dateTime('dispatched_at')->useCurrent();
            $table->unsignedInteger('created_by');
            $table->decimal('total_purchase_value', 22, 4)->default(0);
            $table->decimal('total_sell_value', 22, 4)->default(0);
            $table->decimal('total_compensation_value', 22, 4)->default(0);
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'final'])->default('final');
            $table->timestamps();

            $table->index(['business_id', 'location_id', 'dispatched_at'], 'dispatch_damages_business_location_date');

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispatch_damages');
    }
};
