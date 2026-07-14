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
        Schema::create('damage_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('business_id');
            $table->unsignedInteger('location_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('variation_id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->unsignedInteger('category_id')->nullable();
            $table->unsignedInteger('unit_id')->nullable();
            $table->unsignedInteger('customer_id')->nullable();
            $table->unsignedInteger('supplier_id')->nullable();
            $table->string('reference_no')->nullable();
            $table->enum('dispatch_status', ['not_dispatched', 'partial', 'dispatched'])->default('not_dispatched');
            $table->dateTime('reported_at')->useCurrent();
            $table->decimal('quantity', 22, 4);
            $table->decimal('dispatched_quantity', 22, 4)->default(0);
            $table->decimal('unit_purchase_price', 22, 4)->default(0);
            $table->decimal('unit_sell_price', 22, 4)->default(0);
            $table->decimal('purchase_value', 22, 4)->default(0);
            $table->decimal('sell_value', 22, 4)->default(0);
            $table->decimal('expected_compensation', 22, 4)->default(0);
            $table->enum('compensation_basis', ['manual', 'purchase', 'sell'])->default('purchase');
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by');
            $table->timestamps();

            $table->index(['business_id', 'location_id', 'dispatch_status'], 'damage_records_business_location_status');

            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
            $table->foreign('location_id')->references('id')->on('business_locations')->onDelete('set null');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('variation_id')->references('id')->on('variations')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('set null');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
            $table->foreign('unit_id')->references('id')->on('units')->onDelete('set null');
            $table->foreign('customer_id')->references('id')->on('contacts')->onDelete('set null');
            $table->foreign('supplier_id')->references('id')->on('contacts')->onDelete('set null');
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
        Schema::dropIfExists('damage_records');
    }
};
