<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePagespeedSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pagespeed_settings', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('business_id')->unsigned();
            $table->foreign('business_id')->references('id')->on('business')->onDelete('cascade');

            // General Settings
            $table->boolean('enabled')->default(true);

            // Cache Settings
            $table->boolean('page_cache_enabled')->default(true);
            $table->integer('page_cache_lifetime')->default(86400);
            $table->boolean('database_cache_enabled')->default(true);
            $table->boolean('cache_mobile_separate')->default(true);
            $table->boolean('cache_logged_in_users')->default(false);

            // HTML Minification
            $table->boolean('minify_html')->default(true);
            $table->boolean('remove_html_comments')->default(true);

            // CSS Optimization
            $table->boolean('minify_css')->default(true);
            $table->boolean('combine_css')->default(true);
            $table->boolean('inline_critical_css')->default(false);

            // JavaScript Optimization
            $table->boolean('minify_js')->default(true);
            $table->boolean('combine_js')->default(true);
            $table->boolean('defer_js')->default(true);

            // Image Optimization
            $table->boolean('lazy_load_images')->default(true);
            $table->boolean('lazy_load_iframes')->default(true);
            $table->integer('exclude_first_images')->default(3);

            // GZIP Compression
            $table->boolean('gzip_enabled')->default(true);
            $table->integer('gzip_level')->default(6);

            // Browser Caching
            $table->boolean('browser_cache_enabled')->default(true);

            // Preloading
            $table->boolean('preload_enabled')->default(true);
            $table->boolean('dns_prefetch_enabled')->default(true);

            // CDN
            $table->boolean('cdn_enabled')->default(false);
            $table->string('cdn_url')->nullable();

            // Advanced
            $table->boolean('remove_query_strings')->default(true);
            $table->boolean('disable_emojis')->default(false);

            // Statistics
            $table->bigInteger('cache_hits')->default(0);
            $table->bigInteger('total_requests')->default(0);
            $table->decimal('avg_load_time', 10, 2)->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pagespeed_settings');
    }
}
