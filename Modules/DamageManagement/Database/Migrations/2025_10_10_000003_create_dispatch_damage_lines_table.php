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
        Schema::create('dispatch_damage_lines', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dispatch_damage_id');
            $table->unsignedBigInteger('damage_record_id');
            $table->decimal('dispatched_quantity', 22, 4);
            $table->decimal('purchase_value', 22, 4)->default(0);
            $table->decimal('sell_value', 22, 4)->default(0);
            $table->decimal('compensation_amount', 22, 4)->default(0);
            $table->timestamps();

            $table->foreign('dispatch_damage_id')
                ->references('id')
                ->on('dispatch_damages')
                ->onDelete('cascade');

            $table->foreign('damage_record_id')
                ->references('id')
                ->on('damage_records')
                ->onDelete('cascade');

            $table->unique(['dispatch_damage_id', 'damage_record_id'], 'damage_dispatch_line_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('dispatch_damage_lines');
    }
};

