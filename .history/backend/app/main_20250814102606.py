from fastapi import FastAPI
from .core.config import get_settings
from .api.routes import register_routes
from .core.tenant import TenantMiddleware
from . import models  # noqa: F401  # import models to register mappers

settings = get_settings()

app = FastAPI(title=settings.app_name, debug=settings.debug)
app.add_middleware(TenantMiddleware)

register_routes(app)

@app.get("/")
async def root():
    return {"message": "AI Realty Assistant API", "environment": settings.environment}
