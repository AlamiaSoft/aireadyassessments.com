<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Assessment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Illuminate\Validation\ValidationException;

#[Name('assessment.inspect')]
#[Description('Inspects one assessment lifecycle by UUID. Returns only status/timestamps/failure code/request ID; excludes candidate data, source documents, and report content.')]
class AssessmentInspectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => ['required', 'uuid'],
        ]);

        $assessment = Assessment::query()
            ->select([
                'id',
                'status',
                'request_id',
                'failure_code',
                'processing_started_at',
                'completed_at',
                'created_at',
                'updated_at',
            ])
            ->find($validated['id']);

        if ($assessment === null) {
            throw ValidationException::withMessages([
                'id' => ['No assessment exists for the supplied UUID.'],
            ]);
        }

        return Response::structured($assessment->toArray());
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->string()
                ->description('UUID of the assessment to inspect.')
                ->required(),
        ];
    }
}
