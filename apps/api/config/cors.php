<?php

$origins = array_filter(array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173'))));

return [
    'paths' => ['api/*', 'mcp/diagnostics'],
    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],
    'allowed_origins' => $origins,
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'MCP-Protocol-Version',
        'X-Assessment-Token',
        'X-Request-ID',
    ],
    'exposed_headers' => ['X-Request-ID', 'Mcp-Session-Id'],
    'max_age' => 600,
    'supports_credentials' => false,
];
