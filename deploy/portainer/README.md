# Portainer deployment (Oracle VPS)

This Git-backed stack targets the **local Docker engine on the Oracle VPS**. Portainer builds the Laravel image from the checked-out repository; no GHCR package, image-publishing workflow, registry login, or API image variable is required.

The stack runs the API, a one-shot setup/migration service, a queue worker, a scheduler, PostgreSQL, and Redis. The public frontend remains on Cloudflare Pages. This Laravel stack uses its own PostgreSQL, Redis and storage volumes; do not reuse the older Node/SQLite application's `lead_data` volume or database.

## One-time preparation

- Ensure Portainer's endpoint is the local Docker environment on this same VPS. Portainer documents a limitation with Compose `build:` for remote Docker endpoints; if this stack targets a remote Agent endpoint, use a host-side build script instead.
- Ensure the external Docker network exists and is shared with your Cloudflare Tunnel/reverse proxy. The default is `alamia-network`; set `PROXY_NETWORK` to the actual network name if different.
- In Portainer, choose **Stacks → Add stack → Repository**. Set repository to `https://github.com/AlamiaSoft/aireadyassessments.com.git`, branch to `main`, and Compose path to `deploy/portainer/stack.yml`.
- Add the variables from `.env.production.example` in Portainer's **Environment variables** panel (key/value entries), or use Portainer's supported **Load variables from .env file** control if available. Do not create a server-side `stack.env`: this Compose file intentionally does not use `env_file`; it passes variables from Portainer into each application container explicitly.
- Set strong unique PostgreSQL and Redis passwords. Keep `POSTGRES_PASSWORD` equal to `DB_PASSWORD`, and `REDIS_PASSWORD` consistent for both the Redis service and Laravel. Do not commit production secrets.
- Set the actual HTTPS API and frontend URLs and a unique initial admin password of at least 16 characters. `APP_KEY` may be left blank: the one-shot setup service generates it on first deployment and persists it in a dedicated Docker volume. Existing keys are reused; a conflicting manually supplied key stops deployment rather than rotating it silently. If the key volume is missing but the database already has applied migrations, setup refuses to generate a replacement key. Back up the `app_key` volume securely alongside the database and restore it if lost.
- Configure the primary and fallback AI endpoint, model and API keys when ready. Blank AI values are allowed for initial deployment only if the application accepts the unconfigured provider state.
- Runtime MCP diagnostics are opt-in. Leave `MCP_ENABLED=false` until you configure a unique bearer token of at least 32 characters and restrict access at the network/Cloudflare layer. Never publish this endpoint without authentication.

## Deploy

1. Confirm the external proxy network exists on the Docker endpoint.
2. Deploy the stack. The API service's `build:` configuration builds `aireadyassessment-api:local` locally; setup waits for PostgreSQL, runs migrations and provisions the configured admin before API/worker/scheduler start.
3. Inspect the `setup` container logs. If migrations or admin provisioning fail, fix the error and redeploy.
4. Verify the API health endpoint through the private origin before routing public traffic.

## Database and admin safety

- The setup service runs `php artisan migrate --force`. Laravel tracks completed migrations; it does not reset, truncate, or recreate the database. New pending migration code still executes, so review schema changes and keep backups.
- `DatabaseSeeder` uses an idempotent upsert for the configured admin account. It creates the account if missing and updates only that email's name, password hash and admin flag on redeployment. Keep credentials stable; changing the configured password and redeploying intentionally rotates that account's password.
- Do not put unrelated demo or fixture seeders in the production `DatabaseSeeder`.

## Before accepting real CVs

Verify private document storage and retention cleanup, AI provider fallback and budget limits, monitoring/logging, automated database backups and a successful restore test. The API uses FrankenPHP/Caddy in classic request mode, with security headers and structured stdout logs. Laravel Octane is deliberately not enabled yet; add it only after request-level benchmarks and long-lived worker safety tests show a benefit.
