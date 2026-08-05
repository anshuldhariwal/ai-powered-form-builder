# FormForge AI

FormForge AI is an assignment project for building, publishing, and processing structured forms with AI-assisted generation and editing. The repository currently contains the completed Milestone 0 foundation: a Laravel JSON backend, React frontend, FastAPI AI boundary, queue worker, databases, container orchestration, authentication foundation, and quality gates.

Product-domain functionality begins in Milestone 1 and is not implemented yet.

## Technology stack

- PHP 8.4 and Laravel 13
- React 19, Vite 8, Tailwind CSS 4, and SortableJS
- MySQL 8.4 and Redis 8
- Laravel Horizon for queued work
- Python 3.12, FastAPI, and uv
- Nginx and Docker Compose
- Pest, Pint, Larastan, ESLint, Ruff, Mypy, Pytest, and GitHub Actions

The Laravel 13 and PHP 8.4 selections are documented deviations from the assignment's Laravel 10/11 baseline. See [DECISIONS.md](DECISIONS.md) for the security-advisory rationale and all approved design choices.

## Repository layout

```text
.
|-- ai-service/        FastAPI service and Python tests
|-- contracts/         Shared JSON Schema contracts (introduced in Milestone 1)
|-- docker/            PHP, Nginx, MySQL, Redis, and Node images
|-- samples/imports/   Assignment import fixtures (added with import features)
|-- src/               Laravel application and React source
|-- .github/workflows/ Continuous integration
|-- compose.yaml       Default development stack
`-- DECISIONS.md       Approved architecture and trade-offs
```

## Prerequisites

- Docker Desktop or another Docker Engine with Docker Compose v2
- Git

PHP, Composer, Node, Python, MySQL, and Redis do not need to be installed on the host for the application stack.

## Quick start

1. Create the local orchestration environment file:

   ```powershell
   Copy-Item .env.example .env
   ```

   On macOS or Linux, use `cp .env.example .env`.

2. Fill these required values in `.env` with unique development secrets:

   ```dotenv
   APP_KEY=base64:<32-random-bytes-encoded-as-base64>
   DB_PASSWORD=<database-user-password>
   DB_ROOT_PASSWORD=<database-root-password>
   AI_SERVICE_SECRET=<shared-Laravel-FastAPI-secret>
   ```

   A Docker-only command for generating the Laravel key value is:

   ```sh
   docker run --rm php:8.4-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
   ```

   LLM settings may remain blank until AI-provider integration is implemented.

3. Build and start the development stack:

   ```sh
   docker compose up --build --wait
   ```

   The first start downloads Composer and npm dependencies into managed Docker volumes and can take several minutes.

4. Create the database tables:

   ```sh
   docker compose exec php php artisan migrate
   ```

5. Open the application at <http://localhost:8080>. Vite HMR listens on <http://localhost:5173> during development.

6. Stop the stack without deleting persistent data:

   ```sh
   docker compose down
   ```

   Add `--volumes` only when intentionally resetting MySQL, Redis, and managed dependency data.

## Services

| Service | Purpose | Host exposure |
| --- | --- | --- |
| `nginx` | Browser entry point and static files | `8080` |
| `php` | Laravel PHP-FPM runtime | None |
| `horizon` | Laravel queue worker | None |
| `mysql` | Persistent application database | None |
| `redis` | Queues, sessions, and cache | None |
| `ai-service` | Internal FastAPI boundary | None |
| `vite` | React development server and HMR | `5173` |
| `app-init` | One-shot Composer volume initializer | None |
| `frontend-init` | One-shot npm volume initializer | None |

All services communicate through one project-scoped bridge network. Only Nginx and the development Vite server publish host ports.

## Health and diagnostics

- `GET /up` checks Laravel process liveness.
- `GET /health` checks MySQL and Redis readiness and returns HTTP 503 when either dependency is unavailable.
- FastAPI `GET /health` is available only inside the Compose network.
- The following command verifies a real queue round trip through Horizon:

  ```sh
  docker compose exec php php artisan queue:smoke
  ```

View service state and logs with:

```sh
docker compose ps
docker compose logs --follow
```

## Quality checks

Run Laravel checks in the PHP container:

```sh
docker compose exec php composer test
docker compose exec php composer analyse
docker compose exec php vendor/bin/pint --test
```

Run frontend checks in the Vite container:

```sh
docker compose exec vite npm run check:dependencies
docker compose exec vite npm run lint
docker compose exec vite npm run build
```

Run Python checks from `ai-service/` on a host with uv installed:

```sh
uv sync --frozen --dev
uv run ruff check .
uv run mypy app tests
uv run pytest
```

GitHub Actions runs PHP, React, Python, and Docker checks in parallel for pushes and pull requests.

## Authentication and internal security

- Laravel Fortify supplies headless registration, login, logout, and password-reset behavior.
- React owns the login and registration interface.
- Browser authentication uses server-side sessions and CSRF protection; Sanctum and public API tokens are not enabled.
- Laravel signs internal FastAPI requests with a shared HMAC secret, timestamp, method, path, and body hash.
- FastAPI health is unauthenticated; protected `/v1` routes reject missing or invalid signatures.
- Real environment files and coding-agent artifacts are ignored and must not be committed.

## Current limitations

- Milestone 0 provides foundations only; tenant, form, schema, submission, import, and AI-provider domain features are not implemented.
- Authentication screens are intentionally minimal and will be integrated with tenant onboarding in Milestone 1.
- The FastAPI service has no configured LLM provider yet.
- The default Compose file is development-oriented. Production orchestration and immutable frontend asset packaging remain later hardening work.
- Fresh Compose initialization depends on access to Composer and npm registries and may be slow on the first run.

## Project documentation

- [DECISIONS.md](DECISIONS.md) records every approved design choice, rationale, consequence, and known trade-off.
- [contracts/README.md](contracts/README.md) defines when shared schema artifacts will be introduced.
- [samples/imports/README.md](samples/imports/README.md) documents the placeholder for assignment import fixtures.
