<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Assessment;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('assessment.summary')]
#[Description('Returns aggregate assessment counts by status and recent processing totals. Never returns candidate identifiers, emails, documents, or report content.')]
class AssessmentSummaryTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $byStatus = Assessment::query()
            ->select('status', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('status')
            ->orderBy('status')
            ->get()
            ->mapWithKeys(fn (object $row): array => [(string) $row->status => (int) $row->aggregate])
            ->all();

        return Response::structured([
            'total' => Assessment::query()->count(),
            'by_status' => $byStatus,
            'created_last_24_hours' => Assessment::query()->where('created_at', '>=', now()->subDay())->count(),
            'completed_last_24_hours' => Assessment::query()->whereNotNull('completed_at')->where('completed_at', '>=', now()->subDay())->count(),
            'failed_last_24_hours' => Assessment::query()->where('status', 'failed')->where('updated_at', '>=', now()->subDay())->count(),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
