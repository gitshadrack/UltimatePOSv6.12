<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('daraja_settings')) {
            Schema::create('daraja_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('business_id')->index();
                $table->unsignedInteger('business_location_id')->unique();
                $table->enum('environment', ['sandbox', 'production'])->default('sandbox');
                $table->text('consumer_key')->nullable();
                $table->text('consumer_secret')->nullable();
                $table->string('business_shortcode')->nullable()->index();
                $table->text('passkey')->nullable();
                $table->enum('transaction_type', ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'])->default('CustomerPayBillOnline');
                $table->string('account_reference')->default('UltimatePOS');
                $table->string('callback_url')->nullable();
                $table->string('confirmation_url')->nullable();
                $table->string('validation_url')->nullable();
                $table->enum('c2b_response_type', ['Completed', 'Cancelled'])->default('Completed');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');
                $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('cascade');
            });
        }

        if (! Schema::hasTable('daraja_payments')) {
            Schema::create('daraja_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('daraja_setting_id')->nullable()->index();
                $table->unsignedInteger('business_id')->nullable()->index();
                $table->unsignedInteger('business_location_id')->nullable()->index();
                $table->unsignedInteger('contact_id')->nullable()->index();
                $table->unsignedInteger('transaction_payment_id')->nullable()->index();
                $table->enum('source', ['stk', 'c2b'])->default('stk')->index();
                $table->string('merchant_request_id')->nullable()->index();
                $table->string('checkout_request_id')->nullable()->unique();
                $table->string('transaction_code')->nullable()->unique();
                $table->string('phone_number')->nullable()->index();
                $table->decimal('amount', 22, 4)->default(0);
                $table->string('business_shortcode')->nullable()->index();
                $table->string('account_reference')->nullable()->index();
                $table->string('status')->default('PENDING')->index();
                $table->integer('result_code')->nullable()->index();
                $table->text('result_description')->nullable();
                $table->dateTime('transaction_date')->nullable();
                $table->enum('reconciliation_status', ['unassigned', 'matched', 'attached', 'ignored'])->default('unassigned')->index();
                $table->string('match_reason')->nullable()->index();
                $table->text('match_note')->nullable();
                $table->boolean('auto_attached')->default(false)->index();
                $table->boolean('is_attached')->default(false)->index();
                $table->unsignedInteger('attached_by')->nullable();
                $table->dateTime('attached_at')->nullable();
                $table->json('request_payload')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['business_location_id', 'status', 'is_attached']);
                $table->foreign('daraja_setting_id')->references('id')->on('daraja_settings')->onDelete('set null');
                $table->foreign('business_id')->references('id')->on('business')->onDelete('set null');
                $table->foreign('business_location_id')->references('id')->on('business_locations')->onDelete('set null');
                $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('set null');
                $table->foreign('transaction_payment_id')->references('id')->on('transaction_payments')->onDelete('set null');
                $table->foreign('attached_by')->references('id')->on('users')->onDelete('set null');
            });
        }

        Permission::firstOrCreate(['name' => 'daraja.manage', 'guard_name' => 'web']);
    }

    public function down()
    {
        Permission::where('name', 'daraja.manage')->where('guard_name', 'web')->delete();
        Schema::dropIfExists('daraja_payments');
        Schema::dropIfExists('daraja_settings');
    }
};
