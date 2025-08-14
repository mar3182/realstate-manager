from functools import lru_cache

try:  # Pydantic v2 preferred
    from pydantic_settings import BaseSettings  # type: ignore
except ImportError:  # fallback to v1 style if pydantic-settings not installed
    from pydantic import BaseSettings  # type: ignore

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

    # pydantic v2: settings_config; v1: Config
    class Config:  # type: ignore
        env_file = ".env"
        case_sensitive = False

@lru_cache
def get_settings() -> Settings:
    return Settings()
