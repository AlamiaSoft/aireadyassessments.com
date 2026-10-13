<?php

namespace App\Services;

use App\Models\Assessment;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class AssessmentPipeline
{
    public function __construct(
        private readonly DocumentTextExtractor $extractor,
        private readonly AiAssessmentService $ai,
    ) {}

    public function process(Assessment $assessment): array
    {
        $diskName = (string) config('assessment.private_disk', 'local');
        $disk = Storage::disk($diskName);
        if (! $disk->exists($assessment->source_file_path)) {
            throw new RuntimeException('SOURCE_DOCUMENT_NOT_FOUND');
        }

        $extension = strtolower(pathinfo($assessment->source_file_name, PATHINFO_EXTENSION));
        $path = $disk->path($assessment->source_file_path);
        if (! is_file($path) || ! hash_equals($assessment->source_file_sha256, (string) hash_file('sha256', $path))) {
            throw new RuntimeException('SOURCE_DOCUMENT_INTEGRITY_FAILED');
        }

        $resumeText = $this->extractor->extract(
            $path,
            $extension,
            (int) config('assessment.max_text_chars', 30000),
        );

        $report = $this->ai->assess($assessment, $resumeText);
        $report['validator'] = [
            'version' => '0.1.0',
            'input_text_extracted' => true,
            'source_integrity_verified' => true,
            'output_schema_validated' => true,
            'ats_claim' => 'No universal ATS pass is claimed. This is a CV-to-job evidence assessment, not an employer ATS certification.',
        ];

        return $report;
    }
}
