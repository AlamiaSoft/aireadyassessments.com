<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Illuminate\Validation\ValidationException;

#[Name('database.schema')]
#[Description('Lists database table names or columns for one existing table. Does not read table rows or execute arbitrary SQL.')]
class DatabaseSchemaTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $validated = $request->validate([
            'table' => ['sometimes', 'nullable', 'string', 'max:128'],
        ]);

        $tables = collect(Schema::getTables())
            ->map(fn (array $table): string => (string) ($table['name'] ?? ''))
            ->filter()
            ->sort()
            ->values();

        $tableName = $validated['table'] ?? null;

        if ($tableName === null || $tableName === '') {
            return Response::structured([
                'count' => $tables->count(),
                'tables' => $tables->take(100)->all(),
                'truncated' => $tables->count() > 100,
            ]);
        }

        if (! $tables->contains($tableName)) {
            throw ValidationException::withMessages([
                'table' => ['The requested table does not exist in the current database.'],
            ]);
        }

        return Response::structured([
            'table' => $tableName,
            'columns' => Schema::getColumns($tableName),
        ]);
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [
            'table' => $schema->string()
                ->description('Optional existing table name. Omit to list table names only.')
                ->nullable(),
        ];
    }
}
