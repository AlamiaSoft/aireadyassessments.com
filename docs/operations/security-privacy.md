# Security and Privacy Baseline

This product processes CVs and job descriptions, which may include contact details, employment history, education and other personal data. Treat all uploaded content as sensitive.

## Requirements before public launch

- HTTPS everywhere; keep backend behind the existing Cloudflare Tunnel or a hardened TLS reverse proxy.
- Secret values live in server environment/secret management only. Commit `.env.example`, never `.env`.
- Validate upload size, detected file type and content; store with randomized names outside public webroot; block executable content.
- Authorize each read/download against the owning user/order; use expiring signed download URLs or controller-mediated streaming.
- Define retention and deletion rules for source files, outputs and derived text; provide a user-visible deletion path.
- Do not retain prompts/files in standard logs; send minimum data to model providers and disclose third-party processing in the privacy notice.
- Escape user-supplied text in rendered output and protect against prompt injection within CV/JD content; uploaded content is data, not system instruction.
- Rate limit expensive operations and protect against denial-of-wallet attacks.
- Verify payment signatures, use idempotent webhook processing, and do not trust browser redirects as evidence of payment.
- Use CSRF and CORS settings appropriate to the selected auth strategy; restrict CORS to deployed frontend origins.
- Use Filament admin authentication with MFA if practical, least-privilege roles and separate production access.
- Back up PostgreSQL and test restore; encrypt backups and document key handling.
- Add dependency audits and static checks in CI; pin runtime/package versions.
- Publish privacy policy, terms, refunds policy and transparent limits of ATS claims before taking payments.

## Model/provider privacy

- Keep model/provider API keys server-side.
- Configure gateway logs to avoid storing prompt/completion content unless specifically needed and disclosed.
- Do not assume providers share identical retention/training settings. Verify current data policy and account configuration before routing CV data.
- Never send candidate data to a public demo endpoint or free provider without an approved data-processing posture.

## Claims policy

Allowed: “This document passed our named validation suite and supported-parser tests as of validator version X.”

Not allowed: “Guaranteed to pass every ATS,” “guaranteed interview,” “guaranteed job,” or score-as-hiring-probability, unless supported by a clearly defined and defensible methodology and carefully scoped wording.
