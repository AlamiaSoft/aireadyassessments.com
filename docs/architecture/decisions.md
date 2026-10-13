# Architecture Decisions

Status: accepted initial direction; implementation details remain subject to CI and integration verification.

## ADR-001 — Laravel API and operations UI

**Decision:** use Laravel as the product backend and API on the Oracle Always Free VPS; use Filament as the admin console within the same Laravel deployment.

**Why:** shares authentication, authorization, business rules and operational records; avoids a second backend and separate static admin app.

**Consequences:** public frontend must call a versioned API. Admin routes require strong authentication, least privilege and separate access controls.

## ADR-002 — Static public frontend on Cloudflare Pages

**Decision:** public marketing/assessment frontend deploys from GitHub to Cloudflare Pages.

**Why:** low operational burden, previews and a distinct public UI deployment lifecycle.

**Consequence:** protect API with origin controls, validation, rate limits, CSRF/CORS configuration appropriate to auth mode, and abuse limits. Avoid placing secrets in frontend variables.

## ADR-003 — PostgreSQL as business record

**Decision:** use PostgreSQL for users, assessments, workflow state, orders, events and metering. Store files separately with private object storage or protected local storage behind an adapter; don't store document bytes in business-event logs.

**Why:** transactional integrity and durable reporting.

## ADR-004 — Asynchronous document workflows

**Decision:** assessment/report generation runs as queued, idempotent jobs. User requests return a workflow ID and a clear state; retries are bounded and observable.

**Acceptance:** duplicate webhook delivery or job retries must not double-charge, duplicate purchases, or send conflicting deliverables.

## ADR-005 — AI provider abstraction, Cloudflare AI Gateway first

**Decision:** centralize calls through an application-level AI client interface. Initial route uses Cloudflare AI Gateway's documented OpenRouter endpoint where configuration is available. Configure explicit provider/model fallbacks only after schema/output and quality tests pass.

**Why:** gateway logging/analytics and configurable fallbacks with replaceable underlying providers.

**Consequences:** AI Gateway doesn't make inference free. Enforce per-workflow token limits, provider timeout, concurrency caps, daily/monthly budgets, and logged usage/cost where available. Record model/provider, latency, token counts if returned, gateway request ID, outcome, and estimated cost. Never log secrets or full CV/JD prompts in ordinary logs.

**Source:** https://developers.cloudflare.com/ai-gateway/usage/providers/openrouter/ and https://developers.cloudflare.com/ai-gateway/configuration/fallbacks/

## ADR-006 — ATS validation is a testable bounded claim

**Decision:** use Open ATS (MIT) as an isolated alpha evaluation candidate, not as an authoritative ATS certification or a hard production dependency until its code, dependencies, installation, test suite, output stability and performance have been verified in CI.

**Acceptance pipeline (product-controlled):**
1. Validate file type, size and malware-scanning policy.
2. Generate clean DOCX/PDF from canonical candidate data using conventional, accessible layouts.
3. Extract text/structure independently from the generated file.
4. Check mandatory identity/contact, section headings, dates and text preservation.
5. Detect unsupported/ambiguous layout, missing sections and extraction anomalies.
6. Block delivery on mandatory failures; repair/regenerate within bounded attempts, otherwise return a clear failure.
7. Maintain synthetic fixtures and regression tests for supported formats and known parsers.

**Non-claim:** passing these tests does not guarantee that every employer ATS parses, ranks, or selects the CV. Never advertise “guaranteed ATS pass” without a narrowly defined, reproducible test suite and scope statement. Never guarantee hiring outcomes.

## ADR-007 — Usage logs from day one

**Decision:** own business telemetry in the application database and structured logs; treat gateway analytics as the provider-side complement. Every assessment/order/AI operation shares a correlation ID.

**Events:** landing/CTA (where consent and analytics policy allow), assessment started/completed/failed, upload rejected, parse completed/failed, AI call started/succeeded/failed/fallback, validation passed/blocked, checkout initiated, payment verified/failed/refunded, delivery generated/downloaded, abuse/rate-limit event.

**Privacy:** default to metadata, IDs, outcomes, timing and cost counters. Never record credentials, payment details, raw CV contents or full prompts in standard application logs.

## ADR-008 — Payments are idempotent

**Decision:** use a supported gateway only after verifying country/business eligibility and current fees. Isolate payment provider behind an interface. Webhook signatures must be verified; order state updates must be idempotent. A manual-payment fallback may exist but is not the primary scale plan.
