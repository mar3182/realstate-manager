from fastapi import FastAPI
from .core.config import get_settings
from .api.routes import register_routes

settings = get_settings()

app = FastAPI(title=settings.app_name, debug=settings.debug)

register_routes(app)

@app.get("/")
async def root():
    return {"message": "AI Realty Assistant API", "environment": settings.environment}
