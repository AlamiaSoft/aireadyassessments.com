<?php

use App\Http\Middleware\EnsureMcpBearerToken;
use App\Mcp\Servers\DiagnosticsServer;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Facades\Mcp;

if (! config('services.mcp.enabled')) {
    return;
}

Mcp::web('/mcp/diagnostics', DiagnosticsServer::class)
    ->middleware([EnsureMcpBearerToken::class, 'throttle:mcp']);
