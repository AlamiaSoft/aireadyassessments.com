<?php

namespace App\Services;

use App\Models\ApplicationEvent;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class EventRecorder
{
    /**
     * Store small, schema-bounded metadata only. Never pass CV text, file bytes,
     * prompts, model completions, email addresses, IPs or secrets as properties.
     *
     * Telemetry failures must not mask the business operation.
     */
    public function record(
        string $eventName,
        ?string $requestId = null,
        ?string $workflowId = null,
        ?string $assessmentId = null,
        array $properties = [],
    ): void {
        $safeProperties = [];
        foreach (array_slice($properties, 0, 20, true) as $key => $value) {
            if (! is_string($key) || preg_match('/(email|cv|resume|prompt|completion|token|secret|password|content|text|ip|path)/i', $key)) {
                continue;
            }

            if (is_bool($value) || is_int($value) || is_float($value) || (is_string($value) && strlen($value) <= 120) || $value === null) {
                $safeProperties[$key] = $value;
            }
        }

        try {
            ApplicationEvent::query()->create([
                'event_name' => Str::limit($eventName, 100, ''),
                'occurred_at' => now(),
                'request_id' => $requestId,
                'workflow_id' => $workflowId,
                'assessment_id' => $assessmentId,
                'properties' => $safeProperties,
            ]);
        } catch (Throwable $exception) {
            Log::warning('telemetry.persist_failed', [
                'event_name' => $eventName,
                'exception_class' => $exception::class,
                'request_id' => $requestId,
                'workflow_id' => $workflowId,
            ]);
        }

        Log::info('product.event', [
            'event_name' => $eventName,
            'request_id' => $requestId,
            'workflow_id' => $workflowId,
            'assessment_id' => $assessmentId,
            'properties' => $safeProperties,
        ]);
    }
}
