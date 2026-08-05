FROM ghcr.io/astral-sh/uv:0.9.30-python3.12-bookworm-slim

ENV PATH="/app/.venv/bin:$PATH" \
    UV_COMPILE_BYTECODE=1 \
    UV_LINK_MODE=copy

WORKDIR /app

COPY ai-service/pyproject.toml ai-service/uv.lock ./

RUN uv sync --frozen --no-dev --no-install-project

COPY ai-service/app ./app

CMD ["uvicorn", "app.main:app", "--host", "0.0.0.0", "--port", "8000"]
