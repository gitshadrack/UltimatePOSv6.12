<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasTable('intasend_settings')) {
            Schema::create('intasend_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('business_location_id')->unique();
                $table->string('till_or_paybill_number')->nullable()->index();
                $table->string('intasend_public_key')->nullable();
                $table->text('intasend_secret_key')->nullable();
                $table->string('webhook_secret')->nullable();
                $table->boolean('require_webhook_signature')->default(0);
                $table->string('payment_link_url')->nullable();
                $table->boolean('is_active')->default(1);
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('intasend_payments')) {
            Schema::create('intasend_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->unsignedInteger('transaction_payment_id')->nullable()->index();
                $table->string('till_number')->nullable()->index();
                $table->string('transaction_code')->unique();
                $table->string('phone_number')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('status')->nullable()->index();
                $table->string('api_ref')->nullable()->index();
                $table->enum('reconciliation_status', ['unassigned', 'matched', 'attached', 'ignored'])->default('unassigned')->index();
                $table->string('match_reason')->nullable()->index();
                $table->text('match_note')->nullable();
                $table->boolean('auto_attached')->default(false)->index();
                $table->boolean('is_attached')->default(false)->index();
                $table->unsignedInteger('attached_by')->nullable();
                $table->dateTime('attached_at')->nullable();
                $table->text('note')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['business_location_id', 'is_attached']);
                $table->foreign('business_id')->references('id')->on('business')->onDelete('set null');
                $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
                $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
                $table->foreign('transaction_payment_id')->references('id')->on('transaction_payments')->onDelete('set null');
                $table->foreign('attached_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        if (! Permission::where('name', 'intasend.manage')->exists()) {
            Permission::create(['name' => 'intasend.manage']);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Permission::where('name', 'intasend.manage')->delete();

        Schema::dropIfExists('intasend_payments');
        Schema::dropIfExists('intasend_settings');
    }
};
