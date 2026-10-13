# Public Assessment API Contract

Status: contract defined; Laravel endpoint is not yet implemented. Frontend must not be described as live until the API and worker are deployed and tested.

## POST /api/v1/assessments

Multipart form submission:
- `email`: required valid email, max 254
- `cv`: required PDF or DOCX, max 10 MiB; validate signature and parser, not only the browser MIME
- `job_title`: optional string max 160
- `job_description`: required text, 80–30,000 chars
- `consent`: required affirmative value

Server controls:
- rate limit by network and account/session; avoid logging raw IP where not operationally necessary
- random private filename, malware/file validation and quotas
- persist an assessment with `queued` state, then enqueue an idempotent workflow
- return HTTP 202 with `{ "data": { "id": "opaque-public-id", "status": "queued" } }`
- never synchronously run high-cost model calls inside the upload request

## GET /api/v1/assessments/{id}

Requires authenticated owner or high-entropy signed status token with expiry. Return only current state, timestamps, safe error code and available result metadata—not source CV content.

## Status state machine

`queued -> processing -> validating -> completed`

Terminal alternatives: `failed`, `needs_action`, `expired`.

Every transition records an immutable event with a workflow ID. Retries are bounded and idempotent.

## Error envelope

`{ "message": "Human-readable message", "errors": { "field": ["Validation error"] } }`

Do not expose stack traces, provider payloads, file paths, internal IDs, or secrets in public errors.

## Later endpoints

- authenticated assessment result: `GET /api/v1/assessments/{id}/result`
- paid order/checkout: `POST /api/v1/orders`
- verified payment webhooks: provider-specific endpoint
- secure deliverable download: expiring, owner-authorized route
- admin endpoints remain in authenticated Filament; do not expose admin metrics through public endpoints.
