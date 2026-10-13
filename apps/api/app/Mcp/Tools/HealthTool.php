<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('app.health')]
#[Description('Returns sanitized application/runtime health and database/Redis connectivity. Never returns secrets or environment values.')]
class HealthTool extends Tool
{
    public function handle(Request $request): Response
    {
        $database = 'ok';
        try {
            DB::select('select 1');
        } catch (Throwable) {
            $database = 'error';
        }

        $redis = 'not_checked';
        if (config('cache.default') === 'redis' || config('queue.default') === 'redis' || config('database.redis.client')) {
            try {
                Redis::connection()->ping();
                $redis = 'ok';
            } catch (Throwable) {
                $redis = 'error';
            }
        }

        return Response::structured([
            'status' => $database === 'ok' && $redis !== 'error' ? 'ok' : 'degraded',
            'application' => config('app.name'),
            'environment' => config('app.env'),
            'debug_mode' => (bool) config('app.debug'),
            'laravel_version' => app()->version(),
            'php_version' => PHP_VERSION,
            'database_driver' => config('database.default'),
            'database' => $database,
            'redis' => $redis,
            'queue_driver' => config('queue.default'),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
