from typing import Literal

from fastapi import FastAPI
from pydantic import BaseModel

from app.security import InternalAuthenticationMiddleware


class HealthResponse(BaseModel):
    status: Literal["ok"]


app = FastAPI(title="FormForge AI Service", version="0.1.0")
app.add_middleware(InternalAuthenticationMiddleware)


@app.get("/health", response_model=HealthResponse, tags=["system"])
async def health() -> HealthResponse:
    return HealthResponse(status="ok")
