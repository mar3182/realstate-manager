from pydantic import BaseSettings
from functools import lru_cache

class Settings(BaseSettings):
    app_name: str = "AI Realty Assistant"
    environment: str = "development"
    debug: bool = True
    api_v1_prefix: str = "/api/v1"
    database_url: str = "sqlite:///./dev.db"  # Placeholder, swap to Postgres
    openai_api_key: str | None = None
    whatsapp_webhook_token: str | None = None
    stripe_api_key: str | None = None
    jwt_secret_key: str = "change-me"
    jwt_algorithm: str = "HS256"

    class Config:
        env_file = ".env"
        case_sensitive = False

@lru_cache
def get_settings() -> Settings:
    return Settings()
