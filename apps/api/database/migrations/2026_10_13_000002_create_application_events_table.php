<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('application_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('event_name', 100);
            $table->timestampTz('occurred_at');
            $table->uuid('request_id')->nullable()->index();
            $table->uuid('workflow_id')->nullable()->index();
            $table->uuid('assessment_id')->nullable()->index();
            $table->json('properties')->nullable();
            $table->index(['event_name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_events');
    }
};
