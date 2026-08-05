from functools import lru_cache

from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    app_env: str = "local"
    host: str = "0.0.0.0"
    port: int = 8000
    ai_service_secret: str = ""
    ai_service_max_clock_skew_seconds: int = 300
    llm_provider: str = ""
    llm_model: str = ""
    llm_api_key: str = ""
    llm_base_url: str = ""

    model_config = SettingsConfigDict(env_file=".env", extra="ignore")


@lru_cache
def get_settings() -> Settings:
    return Settings()
