<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Mcp\Tools\AssessmentInspectTool;
use App\Models\Assessment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class DiagnosticsToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_inspection_selects_only_operational_fields(): void
    {
        $assessment = Assessment::query()->create([
            'candidate_email' => 'candidate@example.test',
            'job_title' => 'Private role',
            'job_description' => 'Sensitive job description',
            'source_file_path' => 'private/cv.pdf',
            'source_file_name' => 'cv.pdf',
            'source_file_sha256' => hash('sha256', 'cv'),
            'public_token_hash' => hash('sha256', 'token'),
            'request_id' => 'd4c3b2a1-1111-4222-8333-123456789abc',
            'status' => 'queued',
            'report' => ['private' => 'report content'],
        ]);

        $request = new Request(['id' => $assessment->id]);
        $response = app(AssessmentInspectTool::class)->handle($request);

        $encoded = json_encode($response->getStructuredContent());
        $this->assertIsString($encoded);
        $this->assertStringNotContainsString('candidate@example.test', $encoded);
        $this->assertStringNotContainsString('Sensitive job description', $encoded);
        $this->assertStringNotContainsString('report content', $encoded);
        $this->assertStringContainsString('d4c3b2a1-1111-4222-8333-123456789abc', $encoded);
    }
}
