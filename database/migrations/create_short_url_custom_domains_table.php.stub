<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::create($prefix.'custom_domains', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->string('domain')->unique();
            $table->boolean('is_wildcard')->default(false);

            $table->string('verification_token');
            $table->boolean('is_verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->string('dns_record_type')->nullable(); // cname|a|txt — validated at app layer
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamp('disabled_at')->nullable();

            $table->string('root_redirect_url')->nullable();

            $table->timestamps();

            $table->index(['tenant_id']);
            $table->index(['is_verified', 'disabled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('short-url.table_prefix', 'short_url_').'custom_domains');
    }
};
