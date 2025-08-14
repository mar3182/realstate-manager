from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Application settings loaded from environment variables / .env file.

    Pydantic v2 style using SettingsConfigDict for configuration.
    """

    model_config = SettingsConfigDict(env_file=".env", case_sensitive=False)

    app_name: str = "AI Realty Assistant"
    environment: str = "development"
    debug: bool = True
    api_v1_prefix: str = "/api/v1"
    database_url: str = "sqlite:///./dev.db"  # TODO: swap to Postgres in non-dev
    openai_api_key: str | None = None
    whatsapp_webhook_token: str | None = None
    stripe_api_key: str | None = None
    jwt_secret_key: str = "change-me"  # TODO: load from secret manager in prod
    jwt_algorithm: str = "HS256"


@lru_cache
def get_settings() -> Settings:
    return Settings()  # type: ignore[call-arg]
