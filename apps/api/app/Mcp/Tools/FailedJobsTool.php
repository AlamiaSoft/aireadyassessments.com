<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('jobs.failed')]
#[Description('Returns failed-job count and a small list of safe metadata only. Does not expose job payloads, exception traces, or user data.')]
class FailedJobsTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'limit' => ['sometimes', 'integer', 'min:1', 'max:10'],
        ]);
        $limit = (int) ($validated['limit'] ?? 5);

        if (! Schema::hasTable('failed_jobs')) {
            return Response::structured([
                'available' => false,
                'count' => 0,
                'jobs' => [],
                'message' => 'The failed_jobs table does not exist.',
            ]);
        }

        $jobs = DB::table('failed_jobs')
            ->select(['id', 'queue', 'failed_at'])
            ->orderByDesc('failed_at')
            ->limit($limit)
            ->get()
            ->map(fn (object $job): array => [
                'id' => (string) $job->id,
                'queue' => (string) $job->queue,
                'failed_at' => (string) $job->failed_at,
            ])
            ->all();

        return Response::structured([
            'available' => true,
            'count' => DB::table('failed_jobs')->count(),
            'jobs' => $jobs,
        ]);
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'limit' => $schema->integer()
                ->description('Maximum number of failed-job metadata records to return (1–10).')
                ->min(1)
                ->max(10)
                ->default(5),
        ];
    }
}
