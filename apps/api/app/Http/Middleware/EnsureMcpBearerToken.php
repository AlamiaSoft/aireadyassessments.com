<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EnsureMcpBearerToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('services.mcp.enabled')) {
            abort(404);
        }

        $expected = (string) config('services.mcp.token', '');
        if (strlen($expected) < 32) {
            Log::critical('Runtime MCP is enabled without a sufficiently long bearer token.');

            return response()->json(['message' => 'MCP authentication is not configured.'], 503);
        }

        $provided = (string) $request->bearerToken();
        if ($provided === '' || ! hash_equals($expected, $provided)) {
            return response()->json(['message' => 'Unauthenticated.'], 401, [
                'WWW-Authenticate' => 'Bearer',
            ]);
        }

        $response = $next($request);

        Log::info('mcp.request', [
            'request_id' => $request->attributes->get('request_id'),
            'path' => $request->path(),
            'status' => $response->getStatusCode(),
            'ip' => $request->ip(),
        ]);

        return $response;
    }
}
