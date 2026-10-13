# Cloudflare Pages Deployment

Public frontend source directory: `apps/web`. Framework preset: None. Build command: none. Build output directory: `apps/web`.

## Deployment steps

1. Connect `AlamiaSoft/aireadyassessments.com` in Cloudflare Pages.
2. Set root directory to repository root (if prompted), build command empty, output directory `apps/web`.
3. Add the chosen production domain after DNS is verified.
4. Set the public API origin by editing `apps/web/config.js` to the HTTPS backend origin. This value is not a secret. Do not put any API/provider keys into frontend files or Pages public environment variables.
5. Configure API CORS for the exact production and preview domains required. Avoid wildcard origins with credentialed auth.
6. Verify security headers, canonical URL, sitemap and privacy/terms content.

## Backend

Laravel runs on Oracle VPS behind the existing Cloudflare Tunnel (or a hardened TLS reverse proxy). Configure separate API hostname and ensure public database/queue ports are not exposed. See system overview and security baseline.

## Release checks

- homepage, assessment intake and legal pages render on mobile and desktop
- no secret strings in build output
- assessment POST returns expected JSON only after backend implementation
- upload size/type checks enforced server-side
- production privacy policy and operator/support details are completed
- API provider and database health checks do not leak internal information

This file describes setup; it does not imply Pages is deployed or DNS is already configured.
