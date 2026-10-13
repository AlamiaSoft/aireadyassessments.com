<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAssessment;
use App\Models\Assessment;
use App\Services\EventRecorder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundationResponse;
use Throwable;

class AssessmentController extends Controller
{
    public function store(Request $request, EventRecorder $events): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email:rfc', 'max:254'],
            'cv' => ['required', 'file', 'mimes:pdf,docx', 'extensions:pdf,docx', 'max:'.(int) config('assessment.max_upload_kb', 10240)],
            'job_title' => ['nullable', 'string', 'max:160'],
            'job_description' => ['required', 'string', 'min:'.(int) config('assessment.min_job_description_chars', 80), 'max:30000'],
            'consent' => ['accepted'],
        ]);

        $file = $request->file('cv');
        if (! $file || ! $file->isValid()) {
            return response()->json(['message' => 'The CV upload could not be read. Please try again.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $assessmentId = (string) Str::uuid();
        $requestId = (string) $request->attributes->get('request_id', Str::uuid());
        $statusToken = Str::random(64);
        $diskName = (string) config('assessment.private_disk', 'local');
        $extension = strtolower((string) $file->extension());
        if (! in_array($extension, ['pdf', 'docx'], true)) {
            return response()->json(['message' => 'Upload a PDF or DOCX document.'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $storedPath = $file->storeAs('assessments/'.$assessmentId, 'source.'.$extension, $diskName);
            if (! is_string($storedPath)) {
                throw new \RuntimeException('SOURCE_STORE_FAILED');
            }

            $assessment = Assessment::query()->create([
                'id' => $assessmentId,
                'candidate_email' => mb_strtolower(trim($validated['email'])),
                'job_title' => isset($validated['job_title']) ? trim($validated['job_title']) : null,
                'job_description' => trim($validated['job_description']),
                'source_file_path' => $storedPath,
                'source_file_name' => mb_substr(basename($file->getClientOriginalName()), 0, 255),
                'source_file_sha256' => (string) hash_file('sha256', Storage::disk($diskName)->path($storedPath)),
                'public_token_hash' => hash('sha256', $statusToken),
                'request_id' => $requestId,
                'status' => 'queued',
            ]);

            $events->record('assessment.started', $requestId, $assessmentId, $assessmentId, [
                'file_extension' => $extension,
                'file_size_bytes' => (int) $file->getSize(),
                'consent_recorded' => true,
                'source' => 'public_web',
            ]);

            ProcessAssessment::dispatch($assessmentId);

            return response()->json([
                'data' => [
                    'id' => $assessmentId,
                    'status' => 'queued',
                    'status_token' => $statusToken,
                    'request_id' => $requestId,
                ],
            ], Response::HTTP_ACCEPTED)->header('X-Request-ID', $requestId);
        } catch (Throwable $exception) {
            Storage::disk($diskName)->deleteDirectory('assessments/'.$assessmentId);
            Assessment::query()->whereKey($assessmentId)->delete();

            Log::error('assessment.dispatch.failed', [
                'assessment_id' => $assessmentId,
                'request_id' => $requestId,
                'exception_class' => $exception::class,
            ]);

            return response()->json([
                'message' => 'We could not queue this assessment. No payment was taken. Please retry shortly.',
                'request_id' => $requestId,
            ], Response::HTTP_SERVICE_UNAVAILABLE)->header('X-Request-ID', $requestId);
        }
    }

    public function show(Request $request, Assessment $assessment): JsonResponse
    {
        $token = (string) $request->header('X-Assessment-Token', '');
        if ($token === '' || ! hash_equals($assessment->public_token_hash, hash('sha256', $token))) {
            return response()->json(['message' => 'Assessment not found.'], Response::HTTP_NOT_FOUND);
        }

        if ($assessment->created_at && $assessment->created_at->lt(now()->subDays((int) config('assessment.status_token_ttl_days', 7)))) {
            return response()->json(['message' => 'Assessment access has expired.'], Response::HTTP_GONE);
        }

        return response()->json([
            'data' => [
                'id' => (string) $assessment->id,
                'status' => $assessment->status,
                'created_at' => $assessment->created_at?->toISOString(),
                'completed_at' => $assessment->completed_at?->toISOString(),
                'report' => $assessment->status === 'completed' ? $assessment->report : null,
                'failure_code' => $assessment->status === 'failed' ? $assessment->failure_code : null,
            ],
        ])->header('X-Request-ID', (string) $request->attributes->get('request_id', ''));
    }
}
