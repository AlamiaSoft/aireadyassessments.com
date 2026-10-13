<?php

namespace App\Jobs;

use App\Models\Assessment;
use App\Services\AssessmentPipeline;
use App\Services\EventRecorder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessAssessment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 150;
    public bool $failOnTimeout = true;

    public function __construct(public readonly string $assessmentId) {}

    public function handle(AssessmentPipeline $pipeline, EventRecorder $events): void
    {
        $assessment = Assessment::query()->findOrFail($this->assessmentId);
        if ($assessment->status === 'completed' || $assessment->status === 'processing') {
            return;
        }

        $assessment->forceFill([
            'status' => 'processing',
            'processing_started_at' => now(),
            'failure_code' => null,
        ])->save();

        $events->record('assessment.processing.started', $assessment->request_id, (string) $assessment->id, (string) $assessment->id);

        try {
            $report = $pipeline->process($assessment);
            $assessment->forceFill([
                'status' => 'completed',
                'report' => $report,
                'failure_code' => null,
                'completed_at' => now(),
            ])->save();

            $events->record('assessment.completed', $assessment->request_id, (string) $assessment->id, (string) $assessment->id, [
                'gaps_count' => count($report['gaps'] ?? []),
                'next_steps_count' => count($report['next_steps'] ?? []),
            ]);
        } catch (Throwable $exception) {
            Log::error('assessment.processing.failed', [
                'assessment_id' => (string) $assessment->id,
                'request_id' => $assessment->request_id,
                'exception_class' => $exception::class,
                'error_code' => preg_match('/^[A-Z0-9_]{3,64}$/', $exception->getMessage()) ? $exception->getMessage() : 'PROCESSING_FAILED',
            ]);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $assessment = Assessment::query()->find($this->assessmentId);
        if (! $assessment || $assessment->status === 'completed') {
            return;
        }

        $assessment->forceFill([
            'status' => 'failed',
            'failure_code' => 'PROCESSING_FAILED',
            'completed_at' => now(),
        ])->save();

        app(EventRecorder::class)->record('assessment.failed', $assessment->request_id, (string) $assessment->id, (string) $assessment->id, [
            'exception_class' => $exception ? $exception::class : 'unknown',
        ]);
    }
}
