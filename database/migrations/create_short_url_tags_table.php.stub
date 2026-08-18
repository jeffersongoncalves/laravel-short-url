<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::create($prefix.'tags', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable();

            $table->string('name');
            $table->string('color', 7)->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'name']);
        });

        Schema::create($prefix.'tag_short_url', function (Blueprint $table) use ($prefix): void {
            $table->id();
            $table->foreignId('tag_id')->constrained($prefix.'tags')->cascadeOnDelete();
            $table->unsignedBigInteger('short_url_id');
            $table->foreign('short_url_id')->references('id')->on($prefix.'urls')->cascadeOnDelete();

            $table->unique(['tag_id', 'short_url_id']);
        });
    }

    public function down(): void
    {
        $prefix = config('short-url.table_prefix', 'short_url_');

        Schema::dropIfExists($prefix.'tag_short_url');
        Schema::dropIfExists($prefix.'tags');
    }
};
