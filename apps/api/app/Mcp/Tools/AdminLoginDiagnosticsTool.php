<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Route;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;
use Throwable;

#[Name('admin.login.diagnostics')]
#[Description('Read-only diagnostics for Filament admin login routing, Livewire update middleware, MCP switch state, and deployed Filament assets. Never reads credentials, request bodies, session cookies, environment values, or application data.')]
class AdminLoginDiagnosticsTool extends Tool
{
    public function handle(Request $request): ResponseFactory
    {
        $routes = collect(Route::getRoutes()->getRoutes());

        $summarizeRoute = static function (RoutingRoute $route): array {
            $middleware = [];

            try {
                foreach ($route->gatherMiddleware() as $item) {
                    if (is_string($item)) {
                        $middleware[] = class_basename(explode(':', $item, 2)[0]);
                    }
                }
            } catch (Throwable) {
                $middleware = ['middleware_resolution_error'];
            }

            return [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
                'middleware' => array_values(array_unique($middleware)),
            ];
        };

        $adminLoginRoutes = $routes
            ->filter(static fn (RoutingRoute $route): bool => trim($route->uri(), '/') === 'admin/login')
            ->map($summarizeRoute)
            ->values()
            ->all();

        $livewireUpdateRoutes = $routes
            ->filter(static fn (RoutingRoute $route): bool => trim($route->uri(), '/') === 'livewire/update'
                && in_array('POST', $route->methods(), true))
            ->map($summarizeRoute)
            ->values()
            ->all();

        $mcpRoutes = $routes
            ->filter(static fn (RoutingRoute $route): bool => str_contains($route->uri(), 'mcp/diagnostics'))
            ->map(static fn (RoutingRoute $route): array => [
                'uri' => $route->uri(),
                'methods' => $route->methods(),
                'name' => $route->getName(),
            ])
            ->values()
            ->all();

        $assets = [];
        foreach ([
            'css/filament/filament/app.css',
            'fonts/filament/filament/inter/index.css',
        ] as $asset) {
            $path = public_path($asset);
            $exists = is_file($path);
            $assets[$asset] = [
                'exists' => $exists,
                'bytes' => $exists ? filesize($path) : null,
            ];
        }

        return Response::structured([
            'timestamp' => now()->toIso8601String(),
            'application' => [
                'name' => config('app.name'),
                'environment' => config('app.env'),
                'debug_mode' => (bool) config('app.debug'),
                'laravel_version' => app()->version(),
                'configuration_cached' => app()->configurationIsCached(),
                'routes_cached' => app()->routesAreCached(),
            ],
            'mcp' => [
                'enabled' => (bool) config('services.mcp.enabled'),
                'bearer_token_configured' => (string) config('services.mcp.token', '') !== '',
                'bearer_token_length_valid' => strlen((string) config('services.mcp.token', '')) >= 32,
                'diagnostics_route_registered' => $mcpRoutes !== [],
                'diagnostics_routes' => $mcpRoutes,
            ],
            'login' => [
                'generic_login_route_exists' => Route::has('login'),
                'filament_login_routes' => $adminLoginRoutes,
                'livewire_update_post_routes' => $livewireUpdateRoutes,
                'authenticate_session_middleware_on_livewire_route' => collect($livewireUpdateRoutes)
                    ->contains(static fn (array $route): bool => in_array('AuthenticateSession', $route['middleware'], true)),
            ],
            'filament_assets' => $assets,
        ]);
    }

    /** @return array<string, \Illuminate\JsonSchema\Types\Type> */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
