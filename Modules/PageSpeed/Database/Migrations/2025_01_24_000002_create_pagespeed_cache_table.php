<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagespeedCacheTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('pagespeed_cache')) {
            return;
        }

        Schema::create('pagespeed_cache', function (Blueprint $table) {
            $table->increments('id');
            $table->string('cache_key', 191)->unique();
            $table->text('url');
            $table->longText('content');
            $table->boolean('is_mobile')->default(false);
            $table->boolean('is_logged_in')->default(false);
            $table->integer('hits')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['cache_key', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagespeed_cache');
    }
}
