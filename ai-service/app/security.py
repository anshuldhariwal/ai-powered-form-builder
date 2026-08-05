import hashlib
import hmac
import time

from fastapi import Request, status
from fastapi.responses import JSONResponse
from starlette.middleware.base import BaseHTTPMiddleware, RequestResponseEndpoint
from starlette.responses import Response

from app.config import get_settings

SIGNATURE_HEADER = "X-FormForge-Signature"
TIMESTAMP_HEADER = "X-FormForge-Timestamp"


def canonical_request(timestamp: int, method: str, path: str, body: bytes) -> bytes:
    body_hash = hashlib.sha256(body).hexdigest()
    return f"{timestamp}\n{method.upper()}\n{path}\n{body_hash}".encode()


def calculate_signature(secret: str, timestamp: int, method: str, path: str, body: bytes) -> str:
    message = canonical_request(timestamp, method, path, body)
    return hmac.new(secret.encode(), message, hashlib.sha256).hexdigest()


def verify_signature(
    secret: str,
    supplied_signature: str,
    timestamp: int,
    method: str,
    path: str,
    body: bytes,
    max_clock_skew_seconds: int,
    now: int,
) -> bool:
    if abs(now - timestamp) > max_clock_skew_seconds:
        return False

    expected = calculate_signature(secret, timestamp, method, path, body)
    return hmac.compare_digest(expected, supplied_signature)


class InternalAuthenticationMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next: RequestResponseEndpoint) -> Response:
        if not request.url.path.startswith("/v1/"):
            return await call_next(request)

        settings = get_settings()
        if not settings.ai_service_secret:
            return JSONResponse(
                {"detail": "Internal authentication is not configured."},
                status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            )

        supplied_signature = request.headers.get(SIGNATURE_HEADER, "")
        supplied_timestamp = request.headers.get(TIMESTAMP_HEADER, "")

        try:
            timestamp = int(supplied_timestamp)
        except ValueError:
            timestamp = 0

        authenticated = supplied_signature and verify_signature(
            secret=settings.ai_service_secret,
            supplied_signature=supplied_signature,
            timestamp=timestamp,
            method=request.method,
            path=request.url.path,
            body=await request.body(),
            max_clock_skew_seconds=settings.ai_service_max_clock_skew_seconds,
            now=int(time.time()),
        )

        if not authenticated:
            return JSONResponse(
                {"detail": "Invalid internal request signature."},
                status_code=status.HTTP_401_UNAUTHORIZED,
            )

        return await call_next(request)
