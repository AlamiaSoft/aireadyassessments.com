# Runtime MCP diagnostics

AIReadyAssessment includes an opt-in, authenticated Model Context Protocol (MCP) endpoint powered by the official `laravel/mcp` package.

## Endpoint and enablement

- Endpoint path: `/mcp/diagnostics` (POST; MCP Streamable HTTP handled by Laravel MCP).
- Disabled by default: `MCP_ENABLED=false`.
- Authentication: static bearer token from `MCP_BEARER_TOKEN`; token must be at least 32 characters.
- Rate limit: 30 requests/minute per source IP + bearer-token fingerprint.
- The endpoint is registered only when MCP is enabled. If enabled without a valid token, middleware fails closed.
- Keep the app behind Cloudflare Tunnel/reverse proxy and add Cloudflare Access or an equivalent network access policy before enabling remote use. Authentication is required regardless.

Generate a token outside the repository, for example:

```sh
openssl rand -hex 32
```

Set the generated value in Portainer's environment configuration:

```dotenv
MCP_ENABLED=true
MCP_BEARER_TOKEN=REPLACE_WITH_THE_GENERATED_64_CHARACTER_HEX_TOKEN
```

Never commit a real token, paste it into issues, or include it in logs. Redeploy the stack after changing environment values because Laravel's configuration is cached during API startup.

## Initial read-only tools

| Tool | Purpose | Privacy boundary |
| --- | --- | --- |
| `app.health` | Laravel/PHP versions, app environment, database and Redis connectivity | No secrets or environment values |
| `database.schema` | List table names; optionally inspect columns for an existing table | No table rows or arbitrary SQL |
| `jobs.failed` | Failed-job count and up to 10 job IDs/queues/timestamps | No payloads or exception traces |
| `assessment.summary` | Assessment totals and status aggregates | No candidate identifiers |
| `assessment.inspect` | Inspect one assessment lifecycle by UUID | Only status, timestamps, failure code and request ID; no email, document or report content |

Tools are read-only by design. Do not add unrestricted SQL, Tinker, arbitrary Artisan execution, arbitrary filesystem access, or mutation tools to the production server. Add product-specific tools through application services, validate all arguments, bound result sizes, and return only the minimum data needed for diagnosis.

## Development-time MCP and Boost

Laravel Boost is installed as a development dependency and is excluded from the production image by `composer install --no-dev`. Run its installer in a local development environment:

```sh
cd apps/api
composer install
php artisan boost:install
```

Boost supports local coding-agent workflows; it is not the production endpoint. The remote endpoint uses Laravel MCP and its separate bearer-token middleware. Keep local development MCP credentials separate from production credentials.

## Audit and operational notes

Successful authenticated requests emit a structured `mcp.request` log event containing request ID, path, HTTP status and source IP. Request bodies, bearer tokens and tool arguments are intentionally not logged. Failed authentication returns 401; a missing/too-short configured token returns 503.

Before enabling remote access, confirm HTTPS, Cloudflare Access/network restrictions, token handling, log retention, and the intended agent's trust boundary. Rotate the token by changing the Portainer secret and redeploying. This token is a single service identity; use OAuth and per-client identities if multi-agent attribution or delegated user permissions become requirements.
