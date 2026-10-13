<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Http\Middleware\EnsureMcpBearerToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class EnsureMcpBearerTokenTest extends TestCase
{
    public function test_it_rejects_requests_without_a_bearer_token(): void
    {
        config(['services.mcp.enabled' => true, 'services.mcp.token' => str_repeat('x', 48)]);

        $request = Request::create('/mcp/diagnostics', 'POST');
        $response = app(EnsureMcpBearerToken::class)->handle($request, fn () => new Response('ok'));

        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Bearer', $response->headers->get('WWW-Authenticate'));
    }

    public function test_it_fails_closed_when_the_token_is_not_configured(): void
    {
        Log::shouldReceive('critical')->once();
        config(['services.mcp.enabled' => true, 'services.mcp.token' => 'short']);

        $request = Request::create('/mcp/diagnostics', 'POST');
        $response = app(EnsureMcpBearerToken::class)->handle($request, fn () => new Response('ok'));

        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_it_allows_a_matching_bearer_token_and_logs_a_sanitized_audit_event(): void
    {
        $token = str_repeat('safe-token-', 5);
        config(['services.mcp.enabled' => true, 'services.mcp.token' => $token]);
        Log::shouldReceive('info')->once()->with('mcp.request', \Mockery::on(
            fn (array $context): bool => isset($context['path'], $context['status'], $context['ip'])
                && ! array_key_exists('token', $context)
                && ! array_key_exists('body', $context),
        ));

        $request = Request::create('/mcp/diagnostics', 'POST', server: ['REMOTE_ADDR' => '127.0.0.1']);
        $request->headers->set('Authorization', 'Bearer '.$token);
        $response = app(EnsureMcpBearerToken::class)->handle($request, fn () => new Response('ok'));

        $this->assertSame(200, $response->getStatusCode());
    }
}
