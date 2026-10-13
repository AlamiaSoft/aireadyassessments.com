<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('candidate_email');
            $table->string('job_title', 160)->nullable();
            $table->text('job_description');
            $table->string('source_file_path');
            $table->string('source_file_name', 255);
            $table->char('source_file_sha256', 64);
            $table->char('public_token_hash', 64)->unique();
            $table->uuid('request_id')->index();
            $table->string('status', 24)->default('queued')->index();
            $table->json('report')->nullable();
            $table->string('failure_code', 64)->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['candidate_email', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
