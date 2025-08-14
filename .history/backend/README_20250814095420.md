# AI Realty Assistant Backend

MVP FastAPI backend skeleton for the AI Realty Assistant platform.

## Features (MVP scope)

- Health check endpoint `/api/v1/health`
- Property CRUD (initial) `/api/v1/properties`
- AI draft stub `/api/v1/ai/draft`

Multi-tenancy header: `X-Tenant-ID` (defaults to 1 if omitted).

## Quick Start

```bash
python -m venv .venv
source .venv/bin/activate
pip install -r requirements.txt
alembic upgrade head
uvicorn app.main:app --reload
```

Open http://127.0.0.1:8000/docs for Swagger UI.

See also root `DEVELOPER_BRIEF.md` and `ARCHITECTURE.md` for broader context.

## Next Steps

- Implement Alembic migrations (done)
- Add multi-tenant middleware (agency isolation) (added)
- Integrate OpenAI for real content drafting
- Add authentication & JWT issuance
- Webhook endpoint for WhatsApp inbound messages
- Stripe subscription webhooks & plan gating
- Replace startup DDL with migrations (done)
- Add feature gating & plan enforcement (in progress)

### Migrations

Apply database migrations:

```bash
alembic upgrade head
```

Create a new migration after model changes:

```bash
alembic revision --autogenerate -m "describe change"
alembic upgrade head
```

