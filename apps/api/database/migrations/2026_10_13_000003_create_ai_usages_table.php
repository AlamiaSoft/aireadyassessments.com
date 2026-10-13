<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('request_id')->index();
            $table->uuid('assessment_id')->nullable()->index();
            $table->string('task', 64);
            $table->string('provider', 100);
            $table->string('model', 160);
            $table->string('status', 24)->index();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->decimal('estimated_cost_usd', 14, 8)->nullable();
            $table->string('fallback_step', 40)->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();

            $table->index(['provider', 'model', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usages');
    }
};
