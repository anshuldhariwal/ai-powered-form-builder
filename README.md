# FormForge AI

**Live demo:** <https://web-production-99ad.up.railway.app>

**Demo login:** `demo@formforge.test` / `FormForgeDemo2026!`

**Published examples:** [Internship application](https://web-production-99ad.up.railway.app/f/formforge-demo/internship-application) · [Customer feedback](https://web-production-99ad.up.railway.app/f/formforge-demo/customer-feedback)

**Repository:** <https://github.com/anshuldhariwal/ai-powered-form-builder>

FormForge AI is a deadline-focused form-builder MVP built with Laravel 13, React 19, MySQL 8.4, Redis, and a shared JSON Schema contract. It provides a complete manual path from account registration through form creation, immutable versioning, publishing, public completion, and response review.

## Implemented MVP

- Session-based registration and login with CSRF protection.
- Personal tenant provisioning and tenant-isolated form queries.
- Twelve field types available from the builder palette.
- Click-to-add, reorder, rename, require, delete, and raw JSON editing.
- Drag reorder, duplicate, step/section authoring, choice options, and validation controls.
- Strict shared JSON Schema contract with valid/invalid fixtures.
- Semantic limits, unique field keys/IDs, option checks, and regex syntax checks.
- Append-only form versions with canonical SHA-256 no-op detection.
- Separate current and published version pointers.
- Stable tenant/form slugs and public URLs.
- Public rendering and server-side required/email/number/choice validation.
- Submissions tied to the exact published version.
- Authenticated response list.
- Response search, pagination, details, private file handling, and streamed CSV export.
- Queued AI generation/editing with schema validation, preview, explicit acceptance, metadata, and a no-key deterministic fallback.
- Queued DOCX and both field-definition/header-row XLSX imports with warnings and editable JSON review before commit.
- Immutable version history/rollback and client/server conditional rules.
- Two published demo forms from shared fixtures.
- Dockerized MySQL, Redis, PHP-FPM, Nginx, Horizon, Vite, and FastAPI foundation.
- Railway-ready production image with precompiled assets and migration/seeding command.

## Technology

- PHP 8.4, Laravel 13, Fortify, Horizon, Pest, Larastan, Pint
- React 19, Vite 8, Tailwind CSS 4, ESLint
- MySQL 8.4 and Redis 8
- Python 3.12, FastAPI, Ruff, Mypy, Pytest
- Docker Compose and GitHub Actions

## Local setup

1. Create local orchestration settings:

   ```powershell
   Copy-Item .env.example .env
   ```

2. Set `APP_KEY`, `DB_PASSWORD`, `DB_ROOT_PASSWORD`, and `AI_SERVICE_SECRET` in `.env`.

3. Start the stack and initialize data:

   ```sh
   docker compose up --build --wait
   docker compose exec php php artisan migrate --force
   docker compose exec php php artisan db:seed --force
   ```

4. Open <http://localhost:8080> and log in with the configured demo credentials, or register a new account.

The public seeded forms are available at:

- <http://localhost:8080/f/formforge-demo/internship-application>
- <http://localhost:8080/f/formforge-demo/customer-feedback>

## Quality checks

```sh
docker compose exec php composer test
docker compose exec php composer analyse
docker compose exec php vendor/bin/pint --test
docker compose exec vite npm run lint
```

For a writable production frontend build with the read-only development mount:

```powershell
docker run --rm -v "${PWD}\src:/var/www/html" -v formforge_node-modules:/var/www/html/node_modules -w /var/www/html formforge-vite npm run build
```

Python checks run from `ai-service/` with uv:

```sh
uv sync --frozen --dev
uv run ruff check .
uv run mypy app tests
uv run pytest
```

## Database and indexes

- `tenants` and `tenant_user` model many-to-many membership.
- `forms` uses bigint internal keys, ULID public IDs, and unique `(tenant_id, slug)`.
- `form_versions` stores native MySQL JSON and unique `(form_id, version_number)`.
- `forms.current_version_id` and `forms.published_version_id` separate editing from public delivery.
- `form_submissions` stores native JSON against the precise form version and is indexed by form/version and submission time.

Migrations are reversible and were verified against MySQL, not SQLite.

## Shared form contract

[`contracts/form-schema.v1.json`](contracts/form-schema.v1.json) is the source of truth. Laravel loads this root artifact with Opis JSON Schema. Object keys are recursively sorted for canonicalization while layout arrays preserve order; SHA-256 checksums prevent duplicate no-op versions.

Approved defaults bound schemas to 1 MiB, 20 steps, 30 sections per step, 150 fields, 100 options per field, and 300 conditions.

## Main routes

| Method | Route | Purpose |
| --- | --- | --- |
| `GET` | `/api/forms` | Tenant-scoped dashboard data |
| `POST` | `/api/forms` | Create form and version 1 |
| `GET` | `/api/forms/{publicId}` | Load builder data |
| `PUT` | `/api/forms/{publicId}` | Validate and create a version |
| `POST` | `/api/forms/{publicId}/publish` | Publish current version |
| `GET` | `/api/forms/{publicId}/submissions` | Paginated responses |
| `GET` | `/api/public/forms/{tenant}/{form}` | Public schema |
| `POST` | `/api/public/forms/{tenant}/{form}` | Validate and store response |

## Railway deployment

The repository includes [`railway.json`](railway.json) and [`docker/production/Dockerfile`](docker/production/Dockerfile). Create an application service from this GitHub repository plus a managed MySQL service, then configure:

```dotenv
APP_NAME="FormForge AI"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:<generated-key>
APP_URL=https://<generated-domain>
LOG_CHANNEL=stderr
DB_CONNECTION=mysql
DB_HOST=${{MySQL.MYSQLHOST}}
DB_PORT=${{MySQL.MYSQLPORT}}
DB_DATABASE=${{MySQL.MYSQLDATABASE}}
DB_USERNAME=${{MySQL.MYSQLUSER}}
DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
DEMO_PASSWORD=<intentional-public-demo-password>
```

Generate a public domain in Railway networking after deployment. `railway.json` runs migrations before release and checks `/up` for readiness; the production container runs the idempotent demo seed before Apache starts.

## Known limitations / unfinished work

The original assignment scope was intentionally reduced on 2026-08-05 to deliver a stable demonstrable MVP under a one-day deadline.

- The three-role enum exists, but the MVP UI uses the first tenant membership and does not provide tenant switching, invitations, or complete role-policy screens.
- The live demo uses the deterministic AI fallback until `LLM_API_KEY`, `LLM_MODEL`, and optionally `LLM_BASE_URL` are configured; the OpenAI-compatible structured-output provider path is implemented.
- DOCX parsing intentionally treats paragraphs as candidate questions; complex tables and rich document semantics require manual review.
- Import review uses an editable JSON confirmation rather than a full spreadsheet-style mapping grid.
- Archive/unpublish controls and polished schema-diff visualization remain unfinished.
- Railway local upload storage is ephemeral until a persistent volume or object-storage disk is attached.
- Automated browser tests and a live walkthrough recording are unfinished.

The completion order after submission is: add tenant switching/invitations, replace prompt-based import/version review with dedicated screens, attach durable object storage, then add browser automation and visual polish.

## Security notes

- Local and production secrets are untracked.
- Every authenticated form query is scoped through the current user’s tenant membership.
- Public routes expose only a published version.
- Unknown submission keys are rejected.
- Schema size and collection counts are bounded before persistence.
- Internal numeric IDs are not used in public product URLs.

See [`DECISIONS.md`](DECISIONS.md) for the approved architecture and accepted trade-offs.
