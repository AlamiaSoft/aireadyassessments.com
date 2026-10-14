<?php

declare(strict_types=1);

namespace App\Mcp\Servers;

use App\Mcp\Tools\AdminLoginDiagnosticsTool;
use App\Mcp\Tools\AssessmentInspectTool;
use App\Mcp\Tools\AssessmentSummaryTool;
use App\Mcp\Tools\DatabaseSchemaTool;
use App\Mcp\Tools\FailedJobsTool;
use App\Mcp\Tools\HealthTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('AIReadyAssessment Diagnostics')]
#[Version('1.1.0')]
#[Instructions('Read-only operational diagnostics for AIReadyAssessment. Do not request or expose candidate personal data, uploaded documents, report contents, credentials, or unrestricted SQL. Tools return bounded, sanitized operational metadata only.')]
class DiagnosticsServer extends Server
{
    /** @var array<int, class-string<\Laravel\Mcp\Server\Tool>> */
    protected array $tools = [
        HealthTool::class,
        AdminLoginDiagnosticsTool::class,
        DatabaseSchemaTool::class,
        FailedJobsTool::class,
        AssessmentSummaryTool::class,
        AssessmentInspectTool::class,
    ];
}
