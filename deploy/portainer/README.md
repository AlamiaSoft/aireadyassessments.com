# Portainer deployment (Oracle VPS)

This stack is designed for a Portainer instance managing the **local Docker engine on the Oracle VPS**. Portainer builds the Laravel image from the checked-out Git repository; no GHCR package, image-publishing workflow, or registry login is required.

The stack runs the API, a one-shot setup/migration service, a queue worker, a scheduler, PostgreSQL, and Redis. The public website remains on Cloudflare Pages.

## One-time preparation

- Ensure Portainer's endpoint is the local Docker environment on this same VPS. Portainer documents a limitation with Compose `build:` for remote Docker endpoints; if this stack targets a remote Agent endpoint, use a host-side build script instead of assuming Portainer can build remotely.
- Create the external Docker network shared with your Cloudflare Tunnel/reverse proxy. It defaults to `proxy`; change `PROXY_NETWORK` if your existing network has another name.
- Copy `.env.production.example` locally, replace all placeholders, and upload the completed file in Portainer's **Stack → Environment variables → Load variables from .env file** panel. Do not commit the filled file.
- Set a stable, unique `APP_KEY`; use a strong PostgreSQL password and Redis password; set the actual HTTPS API and frontend URLs; and provide the initial admin name, email and a unique password of at least 16 characters.
- Add the primary and fallback AI endpoint, model and API keys. Keep real credentials only in Portainer's environment settings.

## Deploy

1. In Portainer choose **Stacks → Add stack → Repository**.
2. Repository: `https://github.com/AlamiaSoft/aireadyassessments.com.git`; branch: `main`; Compose path: `deploy/portainer/stack.yml`.
3. Upload the completed environment file in the stack's environment-variable panel. Confirm `API_IMAGE=aireadyassessment-api:local` and the existing proxy-network name.
4. Deploy the stack. Portainer/Compose builds the image locally from `apps/api/Dockerfile`; the `setup` service waits for PostgreSQL, runs pending migrations, and then provisions the configured admin before API/worker/scheduler start.
5. Inspect the `setup` container logs. If migrations or admin provisioning fail, the dependent application services will not start; fix the error and redeploy.
6. Verify the API health endpoint through the private origin before routing public traffic.

## Database and admin safety

- The setup service runs `php artisan migrate --force`. Laravel tracks completed migrations, so already-applied migrations are not re-run and the command does not reset, truncate, or recreate the database.
- New pending migration code still executes. Review schema changes and keep database backups; a deliberately destructive migration can still be destructive.
- `DatabaseSeeder` uses an idempotent upsert for the configured admin account. It creates the account if missing and updates only that email's name, password hash and admin flag on redeployment. It does not truncate tables or re-seed other application data. Keep the configured credentials stable; changing the Portainer admin password and redeploying intentionally rotates that account's password.
- Do not put unrelated demo or fixture seeders in the production `DatabaseSeeder`.

## Before accepting real CVs

Verify private document storage and retention cleanup, AI provider fallback and budget limits, monitoring/logging, automated database backups and a successful restore test. The API uses the official FrankenPHP/Caddy runtime in classic request mode, with security headers and structured stdout logs. Laravel Octane is deliberately not enabled yet: this workload moves AI processing to Redis-backed jobs, so Octane should be added after request-level benchmarks and tests for long-lived worker safety demonstrate a real benefit.
