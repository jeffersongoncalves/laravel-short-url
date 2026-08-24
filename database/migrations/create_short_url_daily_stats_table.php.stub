<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::create($prefix.'daily_stats', function (Blueprint $table) use ($prefix) {
            $table->id();
            $table->foreignId('short_url_id')->constrained($prefix.'urls')->cascadeOnDelete();
            $table->date('date');

            $table->unsignedBigInteger('visits_count')->default(0);
            $table->unsignedBigInteger('unique_visits_count')->default(0);
            $table->unsignedBigInteger('bot_visits_count')->default(0);

            $table->jsonb('device_stats')->nullable();
            $table->jsonb('browser_stats')->nullable();
            $table->jsonb('os_stats')->nullable();
            $table->jsonb('country_stats')->nullable();
            $table->jsonb('city_stats')->nullable();
            $table->jsonb('referer_stats')->nullable();
            $table->jsonb('referer_type_stats')->nullable();
            $table->jsonb('utm_source_stats')->nullable();
            $table->jsonb('utm_medium_stats')->nullable();
            $table->jsonb('utm_campaign_stats')->nullable();
            $table->jsonb('language_stats')->nullable();
            $table->jsonb('variant_stats')->nullable();
            $table->jsonb('hourly_stats')->nullable();

            $table->timestamps();

            $table->unique(['short_url_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('short-url.table_prefix', 'short_url_').'daily_stats');
    }
};
