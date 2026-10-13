# Delivery Plan

Objective: ship an automated self-service workflow and validate actual willingness to pay with zero new infrastructure purchases.

## Milestone 0 — Repository and OSS baseline

- [x] Record architecture and non-claims.
- [x] Document OSS candidates and adoption gates.
- [x] Define event schema and operational dashboard metrics.
- [x] Add CI foundation.
- [ ] Add Laravel application skeleton, pinned PHP/Node versions and automated checks.
- [ ] Reproduce Open ATS tests in isolated CI and benchmark dependencies/resources.

## Milestone 1 — Deployable skeleton

- Laravel API + Filament admin.
- PostgreSQL migrations for users/assessments/workflows/orders/events/AI metering.
- Health endpoint and structured logging with correlation ID.
- Docker Compose for local development; production deployment guide for Oracle VPS.
- Cloudflare Pages public frontend shell, versioned API config and deploy workflow.

**Exit:** clean setup instructions work from scratch; CI green; no credentials committed; admin routes protected.

## Milestone 2 — First useful free assessment

- CV upload and target job description form.
- Private file storage, job queue and assessment status polling.
- Parse CV and JD; deterministic match/report schema.
- AI-assisted recommendations with source-grounded evidence references.
- Free tier limits and privacy/retention policy.
- Product usage visible in admin dashboard.

**Exit:** synthetic CV/JD fixture completes end to end; retry and failure states are visible and recoverable.

## Milestone 3 — Paid application package

- Paid product: tailored CV + cover letter + prioritized gap plan.
- Payment provider integration only after current Pakistani merchant eligibility/settlement/fees are verified.
- Verified webhooks, idempotency, receipts and refund state.
- DOCX/PDF generation and deterministic validation gate.
- Download authorization and automated email or on-site delivery.

**Exit:** test-mode payment produces exactly one order and one validated deliverable; invalid documents never reach delivery.

## Milestone 4 — Acquisition and conversion

- Search-intent landing pages and realistic sample reports with synthetic data.
- Free-tool limits that encourage upgrade without deceptive scoring.
- Consent-aware source/campaign attribution and referral code.
- Checkout conversion measurement.
- Simple customer feedback and issue capture.

**Exit:** we can answer: how many users start, finish, pay, successfully receive a document, return, complain or refund—and cost per successful order.

## Milestone 5 — Controlled launch and iteration

- Launch Pakistan-first marketing with global access.
- Start with organic search, existing network and relevant communities; no paid acquisition until conversion is evidenced.
- Instrument feedback and quality; evaluate one pricing change at a time.
- Expand only after measuring conversion, delivery reliability, inference cost, refunds and repeat use.

## Early business validation targets (not forecasts)

Within the first two weeks after public deployment, seek:
- 100 relevant landing visits or qualified sessions
- 20 completed assessments
- 5 paid orders
- 90%+ automatic workflow completion without operator intervention
- Known and bounded AI spend per successful order

If traffic is insufficient, the result is “acquisition not yet tested,” not proof the product lacks demand. If traffic arrives but nobody pays, revisit offer and pricing before adding features.

## Required launch gates

- [ ] Security/privacy baseline
- [ ] Payment verification and idempotency tests
- [ ] File upload abuse tests
- [ ] ATS/parser regression suite
- [ ] Document output text extraction pass
- [ ] Provider timeout/fallback and budget ceiling test
- [ ] Event logging and revenue/cost dashboard
- [ ] Backup/restore and deletion test
- [ ] Privacy, terms, refund and claims policy
