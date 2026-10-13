# Usage Logging and Observability

Logging must be enabled before a public launch. We need to know real usage, conversion, failures and per-order inference cost without storing candidates' private documents in analytics.

## Event envelope

Every event should include:
- `event_id` (UUID; deduplication key)
- `occurred_at` (UTC)
- `event_name`
- `request_id` / `workflow_id` (correlation IDs)
- `user_id` nullable; use an internal identifier, not email
- `assessment_id`, `order_id` where relevant
- `environment`, `release`
- `status`, `duration_ms` where relevant
- bounded, schema-validated `properties` with no PII/document text

Never write raw CV/JD text, files, access tokens, authorization headers, card/bank details or secret provider payloads to this event stream.

## Required events

- `assessment.started`, `assessment.completed`, `assessment.failed`
- `upload.accepted`, `upload.rejected`
- `document.parse.completed`, `document.parse.failed`
- `ai.request.started`, `ai.request.succeeded`, `ai.request.failed`, `ai.request.fallback`
- `document.validation.passed`, `document.validation.blocked`
- `checkout.started`, `payment.succeeded`, `payment.failed`, `payment.refunded`
- `deliverable.generated`, `deliverable.downloaded`
- `rate_limit.triggered`, `abuse.detected`

Do not log every speculative UI event as a business fact. Page-view analytics, if added, must follow the published privacy notice and consent requirements.

## AI request metadata

Record:
- logical task key (e.g. `cv_assessment`, `job_match`, `rewrite_bullet`)
- provider and exact model ID
- gateway request/run ID and fallback step where available
- latency, attempt count, HTTP/provider outcome
- prompt version and schema version
- input/output token counts if the provider returns them
- estimated/provider-reported cost and currency; mark estimates explicitly
- validation pass/fail and retry count

Do not save full prompts/completions by default. For evaluation, use consented synthetic fixtures or explicit opt-in examples with retention limits.

## Dashboards

Minimal admin pages:
1. Funnel: visitors (where measured) → assessment starts → assessment completion → checkout → paid → deliverable success.
2. Reliability: successful/failed jobs, queue age, latency percentiles, fallback rate, validation block rate.
3. Economics: gross revenue, refunds, AI provider spend, estimated AI cost per successful paid order, gross contribution before other operating costs.
4. Acquisition: landing source/campaign and conversion, using consent-aware aggregated values.
5. Product quality: document validation categories and anonymized feedback.

## Cost and abuse protections

- Max upload size, allowed MIME types plus actual file signature checks, request rate limit and user/IP abuse controls.
- Per-step token/output limits, timeout, concurrency guard and bounded retry.
- Daily spend ceiling and a fail-closed or downgrade mode at the ceiling.
- Idempotency keys for queue jobs, payment events, and deliverable generation.
- Alerts for spend spikes, provider failures, repeated validation failures and queue backlog.

## Implementation boundary

Use Laravel's logger for operational logs and a PostgreSQL application-event table for queryable product metrics. Send no secrets or candidate documents to logs. Use Cloudflare AI Gateway analytics as complementary provider telemetry, correlated with local request/workflow IDs. Langfuse remains optional until its resource footprint is validated on the Oracle VPS.
