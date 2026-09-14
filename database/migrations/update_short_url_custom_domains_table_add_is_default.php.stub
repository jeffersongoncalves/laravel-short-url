<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::table($prefix.'custom_domains', function (Blueprint $table) {
            $table->boolean('is_default')->default(false)->after('is_wildcard');
            $table->index(['tenant_id', 'is_default']);
        });
    }

    public function down(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::table($prefix.'custom_domains', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'is_default']);
            $table->dropColumn('is_default');
        });
    }
};
