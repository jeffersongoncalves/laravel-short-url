<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::create($prefix.'folders', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();

            $table->string('name');
            $table->string('color', 7)->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('short-url.table_prefix', 'short_url_').'folders');
    }
};
