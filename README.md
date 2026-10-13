# AIReadyAssessment.com

Career-readiness assessment and job-application improvement platform.

**Product principle:** assess a candidate's existing evidence against a target opportunity, explain gaps, and guide the user toward practical next actions. The product must not promise hiring outcomes or universal compatibility with every third-party ATS.

## Current architecture

- **Agent tooling:** Laravel Boost for local development agents; an opt-in authenticated Laravel MCP endpoint for read-only runtime diagnostics. See [runtime MCP operations](docs/operations/runtime-mcp.md).

- **Public frontend:** static site deployed to Cloudflare Pages from GitHub.
- **Application/API:** Laravel on the existing Oracle Always Free VPS.
- **Admin:** Filament inside the Laravel application.
- **Data:** PostgreSQL initially; Laravel queue driver selected for the VPS footprint and upgrade path.
- **AI:** Cloudflare AI Gateway as an observability/routing layer, with OpenRouter as the initial model catalog and a separately tested fallback. No model is selected solely on a public leaderboard; each task requires quality/cost checks.
- **Operations:** structured application events in PostgreSQL/log output, workflow correlation IDs, exception reporting, and gateway request analytics.
- **ATS validation:** Open ATS (MIT, alpha) is an isolated evaluation candidate. It does not prove compatibility with all commercial ATS products; no universal ATS-pass claim is allowed.

## Product promise

We can enforce: **every generated document must pass our published deterministic validation suite before download**. We cannot guarantee employer selection or universal third-party ATS parsing, which depend on systems and rules we do not control.

## Project status

This repository initially contains architecture, OSS audit, delivery plan, environment template and CI foundations. The production application skeleton is the next implementation increment. See:

- [Architecture decisions](docs/architecture/decisions.md)
- [OSS component audit](docs/oss/component-audit.md)
- [Delivery plan](docs/plan/delivery-plan.md)
- [Usage and AI observability](docs/operations/observability.md)
- [Security and privacy baseline](docs/operations/security-privacy.md)

## Development principles

1. Prefer maintained OSS for complete, well-tested capabilities.
2. Treat OSS projects as dependencies to evaluate, not as proof claims from their READMEs.
3. Build only product-specific orchestration, acceptance checks and gaps.
4. Put quality, privacy, observability and cost controls in the critical path.
5. Keep provider and ATS analyzer integrations replaceable.
6. Never commit credentials, real candidate CVs, or personal data.

## Local prerequisites

- PHP version and extensions supported by the Laravel version selected in the implementation PR
- Composer
- Node.js LTS and npm
- PostgreSQL
- Python 3.10+ only for the isolated Open ATS adapter evaluation

Exact runtime versions and commands will be pinned when the application scaffold is added.
