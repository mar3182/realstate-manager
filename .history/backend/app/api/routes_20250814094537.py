from fastapi import FastAPI
from fastapi import APIRouter

from .v1 import properties, ai, health
from ..core.config import get_settings

settings = get_settings()

api_router = APIRouter(prefix=settings.api_v1_prefix)
api_router.include_router(health.router, tags=["health"])
api_router.include_router(properties.router, prefix="/properties", tags=["properties"])
api_router.include_router(ai.router, prefix="/ai", tags=["ai"])

def register_routes(app: FastAPI):
    app.include_router(api_router)
