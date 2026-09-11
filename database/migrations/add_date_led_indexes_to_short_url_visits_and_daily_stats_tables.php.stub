<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        // The global (site-wide) dashboard aggregates across every short_url_id at
        // once — an id-led index serves that IN (...) list poorly. A date-led index
        // matches the query's real selectivity (a narrow date range). See issue #18.
        Schema::table($prefix.'visits', function (Blueprint $table) {
            $table->index(['visited_at', 'short_url_id']);
        });

        Schema::table($prefix.'daily_stats', function (Blueprint $table) {
            $table->index(['date', 'short_url_id']);
        });
    }

    public function down(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::table($prefix.'visits', function (Blueprint $table) {
            $table->dropIndex(['visited_at', 'short_url_id']);
        });

        Schema::table($prefix.'daily_stats', function (Blueprint $table) {
            $table->dropIndex(['date', 'short_url_id']);
        });
    }
};
