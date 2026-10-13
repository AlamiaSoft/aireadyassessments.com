# OSS Component Audit

Audit date: 2026-10-13. Repository/source links are provided so maintainers can verify the current upstream state before pinning versions. An OSS claim is not production verification.

## Decisions at a glance

| Capability | Candidate / choice | License / state | Decision |
|---|---|---|---|
| ATS resume scoring | [Open ATS](https://github.com/jlynshue/open-ats) | Upstream README/pyproject state MIT, alpha; targets correlation with Jobscan but describes its own validation target | Evaluate in isolated Python adapter. Do not treat scores as ATS certification and do not make it a critical production dependency until tests pass. |
| DOCX input/output | [python-docx](https://python-docx.readthedocs.io/) | MIT; established | Use for DOCX parsing/generation if PHP-side libraries fail project quality checks. Prefer one primary language path; avoid duplicating implementations without need. |
| PDF text extraction | [pdftotext / Poppler](https://poppler.freedesktop.org/) or [pdfplumber](https://github.com/jsvine/pdfplumber) | Poppler is GPL-2.0-or-later; pdfplumber MIT | Prefer an isolated server-side extraction adapter. Verify OS package and container licensing obligations. pdfplumber is a candidate for Python adapter and depends on pdfminer.six. |
| Laravel document generation | [PHPWord](https://github.com/PHPOffice/PHPWord) | LGPL-3.0 | Candidate for DOCX generation. Review LGPL obligations and current maintenance/dependency versions. Alternative: investigate maintained permissive packages if license simplicity is required. |
| PDF generation | [Dompdf](https://github.com/dompdf/dompdf) | LGPL-2.1 | Candidate for rendering a deliberately simple HTML template to PDF. Must test output text order and visual fixtures; do not assume rendered PDF will pass parsing checks. |
| Admin UI | [Filament](https://filamentphp.com/) | MIT | Adopt within Laravel; pin compatible major versions. |
| AI tracing/evaluation | [Langfuse](https://github.com/langfuse/langfuse) | MIT core; self-hosted stack can be resource intensive; some enterprise capabilities are separately licensed | Optional. Start with first-party event/metering table + gateway analytics, then add Langfuse if useful. Do not burden the Oracle VM at launch without resource sizing. |
| AI gateway | [Cloudflare AI Gateway OpenRouter endpoint](https://developers.cloudflare.com/ai-gateway/usage/providers/openrouter/) | Managed service; model inference billed separately | Adopt as an integration option. Verify current account capabilities and pricing; secrets remain backend-only. |
| AI provider backup | Cloudflare AI Gateway [fallbacks](https://developers.cloudflare.com/ai-gateway/configuration/fallbacks/) | Managed routing feature | Configure only models tested for the same output schema, languages, latency and privacy. Fallback means availability, not equal quality or lower cost automatically. |
| Public UI hosting | [Cloudflare Pages](https://developers.cloudflare.com/pages/) | Managed platform | Adopt for static/public frontend and preview deployments. |
| Business analytics | Laravel/PostgreSQL, structured logs | Project-owned | Build only the minimal events and dashboard needed; source-of-truth metrics must not depend on browser analytics. |
| Queueing | Laravel queue abstraction + PostgreSQL or Redis after sizing | Laravel ecosystem | Start with database queue if volume is low and simplicity wins; add Redis when measured throughput requires it. Avoid running extra infra by default. |
| Payments | Country-eligible provider to be verified | Provider-specific | Abstract it. Don't hard-code a payment provider before verifying Pakistan merchant onboarding, international cards, settlement, disputes and fees. |
| Error tracking | Structured logs + health endpoint at first; evaluate [GlitchTip](https://glitchtip.com/) / Sentry-compatible | Verify current license/deploy sizing | Add only if structured logs do not provide sufficient exception triage. |

## Open ATS evaluation gate

Source: https://github.com/jlynshue/open-ats

What makes it useful:
- MIT license in upstream pyproject and repository LICENSE.
- Python package with PDF/DOCX/Markdown/plain text support per project README.
- Transparent scoring, formatting heuristics and recommendations; test tooling and documentation are present.

Why not to claim its output is a guarantee:
- Upstream labels the project alpha.
- Its “90%+ correlation” is described as a target and methodology, not independently reproduced proof in this project.
- ATS products vary by parser/configuration, fields, application workflow and employer filters; similarity to another scanner is not universal ATS conformance.
- The dependency set is non-trivial (spaCy, NLTK, pandas, pdfplumber, python-docx and more) and the current package structure/install needs to be tested against our pinned Python runtime.

Integration strategy:
1. Vendor no code by default; pin a reviewed upstream commit or release in a separate adapter/reproducible environment.
2. Run its upstream tests and our synthetic fixture set in CI.
3. Test PDF/DOCX parsing, malformed inputs, extraction edge cases, resource usage and dependency vulnerabilities.
4. Define a stable JSON boundary so Laravel is not coupled to internal Python classes.
5. If tests are flaky, heavyweight or unreliable, use it as a development-time benchmark and rely on deterministic document validation for our acceptance gate.
6. Preserve upstream license notices if code is copied or redistributed. Dependency on the published MIT package still warrants a lockfile and security checks.

## Selection policy

Before adding any dependency to production:
- License file and transitive dependencies reviewed.
- Activity/maintenance and release provenance reviewed.
- Tests run in CI or a reproducible container.
- Resource and cold-start cost measured on our target deployment.
- No unsupported claim copied from the upstream README.
- Fallback/replacement path considered.
