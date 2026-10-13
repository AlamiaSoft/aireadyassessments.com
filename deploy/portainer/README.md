# Portainer deployment (Oracle VPS)

This stack runs the Laravel API, one queue worker, one Laravel scheduler, PostgreSQL, and Redis. The public marketing website remains on Cloudflare Pages.

## Prerequisites

- The image is published to GHCR by `.github/workflows/publish-api-image.yml` after API/Dockerfile changes on `main`. The workflow builds both `linux/amd64` and `linux/arm64`. Use `ghcr.io/alamiasoft/aireadyassessment-api:latest` initially; for repeatable rollbacks, use the immutable `sha-<commit>` tag from the workflow run. Check the workflow run is green before pulling the image.
- Create `/opt/aireadyassessment/.env` on the VPS with production app/provider settings. Do not commit it. Set strict file permissions (`chmod 600`).
- Create strong `POSTGRES_PASSWORD` and `REDIS_PASSWORD` values in Portainer stack environment variables. Keep them out of tracked YAML.
- `APP_KEY` must be generated once for this environment and kept stable. Generate with `php artisan key:generate --show` using the project image.
- Set `APP_URL`, `FRONTEND_URL`, and `CORS_ALLOWED_ORIGINS` to the real HTTPS API and Cloudflare Pages origins.
- Create the external Docker network used by your Cloudflare Tunnel/reverse proxy (`proxy` by default) before deploying. Do not publish database/cache ports. The backend network allows outbound egress so the worker can call the configured AI provider.

## Deploy

1. Wait for [Publish API image](../../actions/workflows/publish-api-image.yml) to pass. In Portainer, configure the private GitHub repository credentials and the GHCR registry. For a private GHCR package, register `ghcr.io` using your GitHub login and a token with `read:packages` scope; never put that token in YAML.
2. Portainer → Stacks → Add stack → Repository (preferred) or Web editor. For Git mode select `https://github.com/AlamiaSoft/aireadyassessments.com.git`, branch `main`, Compose path `deploy/portainer/stack.yml`.
3. Set `API_IMAGE=ghcr.io/alamiasoft/aireadyassessment-api:latest`, `POSTGRES_PASSWORD`, and `REDIS_PASSWORD` in stack environment variables.
4. Put all Laravel variables in `/opt/aireadyassessment/.env` on the Docker host. Ensure Portainer/Docker can read this file. If your Portainer setup cannot use host `env_file` paths, put the non-secret settings in the stack UI and secrets in its environment-variable section instead.
5. Deploy. Run `php artisan migrate --force` once in the API container. Seed admin only after setting secure `INITIAL_ADMIN_EMAIL` and `INITIAL_ADMIN_PASSWORD`.
6. Confirm `/up` through the private origin before routing public traffic.

## Before real CVs

Verify production image build, PHP extensions including PostgreSQL and Redis clients, private document storage, scheduled document-retention cleanup, email delivery if enabled, AI provider credentials and fallback, logging/alerts, database backups, and a successful restore test. Do not accept real CVs until those are checked.

## Notes

The API uses Laravel's database queue/cache/session by default for local development. This stack overrides them to Redis. Confirm the image contains a supported Redis client extension or package. `php artisan serve` is used here as a bootstrap convenience, not an ideal high-throughput production HTTP server; replace with a hardened Nginx/PHP-FPM or supported Laravel runtime before public production launch.
