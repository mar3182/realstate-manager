# AI Realty Assistant - Architecture Overview

## Vision
A multi-tenant SaaS that ingests unstructured property info (text / images) via WhatsApp or app, converts it into structured listings, & auto-generates marketing content for websites and social channels.

## High-Level Phases
1. MVP (Weeks 1–12): WhatsApp ingestion -> AI draft -> WordPress sync (Starter plan)
2. Phase 1+: Add Pro & Business features (themes, CRM, payments, analytics, mobile app)
3. Phase 2+: Headless CMS migration, Enterprise capabilities (white-label, API, advanced analytics)

## Core Domains
- Ingestion (WhatsApp, future mobile)
- AI Processing (extraction, generation, enrichment)
- Content Management (properties, pages, assets)
- Publishing (WordPress + social APIs)
- Subscription & Billing (Stripe)
- CRM & Payments (Business+)
- Multi-Tenancy & Access Control
- Analytics & Insights

## MVP Component Architecture

```text
[User/Agent]
    | (WhatsApp Msg)
[WhatsApp Business API Webhook]
    |--> Inbound Handler (FastAPI) -- stores raw message
            |--> AI Orchestrator -> OpenAI (text) / Vision (image captions)
            |--> Draft Entities (property draft + generated description + social copy)
            |--> Notification (future push / email)
    |--> Operator reviews draft in Admin UI (Phase 1 web dashboard)
            |--> Approve -> Property Listing -> WordPress Sync Service
```


## Technology Choices
- Backend: FastAPI (async friendly, quick iteration)
- DB: PostgreSQL (schemas, JSONB for AI raw payloads) w/ SQLAlchemy + Alembic
- Cache / Queue (future): Redis (rate limiting, task queue, ephemeral state)
- Storage: S3-compatible bucket for images
- Auth: JWT (first) -> OAuth/Social/SSO (later)
- AI: OpenAI GPT-4.x + vision; abstraction layer for future providers
- WordPress: REST API integration (custom plugin later for performance)
- Mobile: React Native (Phase 1)
- Headless (Phase 2): Next.js + CMS (custom or Sanity/Strapi migration path)

## Multi-Tenancy Strategy
Option: Single DB + tenant_id on all rows.
- Pros: Simpler ops, fewer connections.
- Cons: Harder noisy-neighbor isolation.
Later upgrade path: Schema-per-tenant (if needed) or sharding by region.

Implementation MVP:
- Middleware extracts tenant via subdomain, API key, or JWT claim.
- SQLAlchemy Session scoped w/ tenant filter injection.
- Feature gating service checks plan entitlements.

## Data Model (Early Sketch)
- Agency (tenant)
- User (belongs to Agency, role: owner/admin/agent)
- Subscription (plan, status, stripe_customer_id)
- Property (title, description, price, address, status, metadata JSON)
- PropertyAsset (property_id, type=image, url, ai_tags JSON)
- Draft (type=property_description/social_post, source_text, model_version, content, status)
- Lead (Business+)
- Invoice/PaymentRecord (Business+)

## AI Orchestration
Abstraction layer: AIProvider -> OpenAIProvider
Pipeline example:
1. Normalize input (clean text / OCR / caption images)
2. Extract structured fields (location, price, features)
3. Generate description variants
4. Generate social copy (platform-specific tone)
5. Store drafts (status=draft)
6. Human approval -> publish

## WordPress Sync
- Service with functions: push_property(property_id), update_property, archive_property
- Auth via application password or JWT plugin
- Rate limit + retry w/ backoff

## Subscription & Billing
- Stripe Checkout + Webhooks -> update Subscription table
- Plan entitlements dictionary (code-driven) e.g. FEATURES[plan]["properties.max"]
- Middleware to enforce limits (raise 402/403 style errors)

## Security & Compliance
- Store only necessary PII
- Signed URLs for images
- Audit log table for critical actions (Phase 1+)
- Secrets via environment / secret manager

## Analytics (Later)
- Event table (tenant_id, event_type, metadata JSON, ts)
- Materialized views / OLAP (e.g., ClickHouse or BigQuery) once scale grows

## Scaling Path
1. Single container + Postgres
2. Add Redis + worker queue (RQ / Celery / Dramatiq)
3. Split AI tasks to dedicated workers
4. Introduce gateway + rate limiting per tenant
5. Migrate to headless front-end & plugin-less delivery

## Open Questions
- Image quality scoring needed? (defer)
- Auto translation requirements? (future) 
- GDPR / regional hosting? (phase-dependent)

## Next Implementation Steps
1. Add DB models & migrations
2. Tenant middleware & dependency
3. Actual AI provider integration
4. WordPress service skeleton
5. Auth (signup/login) + JWT
6. Stripe webhook endpoint
7. Basic tests & CI pipeline
