# Engineering Decisions

## Assumptions

No assumptions have been approved yet.

## Decision 001: Repository Structure and Context-Plan Placement

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Partially superseded by Decision 004; the monorepo layout remains approved, but the repository plan copy was removed
- Approved option: Option A — use the planned monorepo layout and keep a repository copy of the implementation plan at the root.

### Context

The project directory was empty, was not initialized as a Git repository, and contained no implementation or coding-agent artifacts to preserve. The implementation plan was stored one directory above the project.

### Alternatives considered

- Option A: Laravel in `/src`, FastAPI in `/ai-service`, shared schemas in `/contracts`, root-level infrastructure and documentation, and a repository copy of `FORM_BUILDER_CODEX_PLAN.md`.
- Option B: Use the same monorepo layout while leaving the implementation plan outside the repository.
- Option C: Install Laravel at the repository root and place the other services beside its application directories.

### Rationale

Option A gives Laravel and FastAPI clear service boundaries, provides a neutral location for the shared JSON Schema contract, and allows root-level Docker and CI orchestration. Keeping the implementation plan with the repository makes its delivery process reproducible and auditable.

### Accepted trade-offs

- Laravel-specific commands will commonly run from `/src`.
- A repository copy of the plan will coexist with the external source document unless that source is deliberately retired later.
- Root-level tooling must coordinate multiple service-specific workflows.

### Consequences

- Laravel application code will live under `/src`.
- FastAPI application code will live under `/ai-service`.
- Shared machine-readable contracts will live under `/contracts`.
- Cross-service infrastructure, documentation, samples, and CI configuration will live at the repository root.
- Coding-agent artifacts remain prohibited inside application-code directories.
- The external plan remains untouched; its repository copy will be added without treating it as application source.

### Follow-up decisions

- Laravel installation layout and exact Laravel 11 version constraint.
- Livewire and Alpine/JavaScript responsibility split.
- Frontend build approach.
- Authentication starter package.

## Decision 003: Use the Current Supported Laravel Major

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option B — supersede the Laravel 11 constraint and use Composer's current supported Laravel release.

### Context

The approved Laravel 11 scaffold selected `laravel/framework:^11.31`, but Composer refused to resolve any matching framework release because every candidate was affected by one or more known security advisories. No dependency lock file or usable vendor installation was produced. Continuing with Laravel 11 would have required explicitly weakening Composer's security-advisory protection.

### Alternatives considered

- Option A: Keep Laravel 11 and explicitly bypass Composer's advisory block.
- Option B: Use the newest supported Laravel major version accepted by Composer.
- Option C: Pause scaffolding and seek reviewer clarification.

### Rationale

Option B preserves secure dependency resolution and the production-minded intent of the assignment. The deviation from the brief is explicit and auditable: Laravel 11 was attempted, but Composer blocked all compatible releases due to known advisories. Silently disabling that protection was rejected.

### Accepted trade-offs

- The implementation uses Laravel 13 instead of the assignment's requested Laravel 11.
- PHP 8.3 or newer is now required by the generated Laravel application.
- Reviewers must evaluate the documented security-driven version deviation.

### Consequences

- The application skeleton is Laravel `13.23.0` under `/src`.
- `composer.lock` records the resolved dependency graph.
- Composer's advisory blocking remains enabled; the initial locked dependency audit reports no known security vulnerability advisories.
- Baseline framework tests pass.

### Follow-up decisions

- Whether to revise Decision 001 and remove the repository copy of the implementation plan.
- Livewire and Alpine/JavaScript responsibility split.
- Frontend build approach.
- Authentication starter package.
- Container, Redis/Horizon, FastAPI packaging, environment, Docker, CI, and local-development choices.

## Why Laravel 11 and Livewire

## Decision 002: Laravel Installation and Version Constraint

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Superseded by Decision 003 after Composer blocked every compatible Laravel 11 release under its security-advisory policy
- Approved option: Option A — install Laravel under `/src` with the explicit `laravel/laravel:^11.0` constraint.

### Context

The approved monorepo structure places the Laravel application under `/src`. The assignment explicitly requires Laravel 11, while an unconstrained Composer installation could select a newer major release.

### Alternatives considered

- Option A: Install `laravel/laravel:^11.0` under `/src`.
- Option B: Install the latest Laravel release without constraining the major version.

### Rationale

Pinning the Laravel 11 major version follows the assignment, makes initial scaffolding reproducible, and still permits compatible Laravel 11 updates.

### Accepted trade-offs

- Laravel-specific commands will normally run from `/src`.
- A later major-version upgrade would require an explicit dependency and application migration.
- The local host currently lacks PHP and Composer, so initial scaffolding may need the available Docker runtime.

### Consequences

- Laravel framework code and Composer dependencies will be created only under `/src`.
- The generated application will begin from Laravel 11 defaults before later approved package and application decisions.
- Baseline framework tests will be run after installation when the runtime permits.

### Follow-up decisions

- Whether to revise Decision 001 and remove the repository copy of the implementation plan.
- Livewire and Alpine/JavaScript responsibility split.
- Frontend build approach.
- Authentication starter package.

## Decision 004: Keep the Implementation Plan Outside the Repository

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — remove the repository copy and retain the external context file.

### Context

Decision 001 originally kept an identical copy of `FORM_BUILDER_CODEX_PLAN.md` at the repository root. The plan is a one-time implementation-control document rather than a product deliverable, and its authoritative external copy remains available at `D:\Anshul Projects\FORM_BUILDER_CODEX_PLAN.md`.

### Alternatives considered

- Option A: Remove the repository copy and retain the external context file.
- Option B: Keep and eventually commit the repository copy.

### Rationale

Removing the duplicate keeps the repository focused on application code and durable project documentation without losing the working context used during implementation.

### Accepted trade-offs

- A future repository clone will not contain the full Codex execution plan.
- Continued plan-driven work depends on access to the external context file during development.

### Consequences

- `FORM_BUILDER_CODEX_PLAN.md` is not part of the project repository.
- `README.md` and `DECISIONS.md` will remain the durable reviewer-facing documentation.
- The external context file remains unchanged and can recreate the copy if needed.

### Follow-up decisions

- Livewire and Alpine/JavaScript responsibility split.
- Frontend build approach.
- Authentication starter package.

## Decision 005: React Frontend with Laravel JSON Endpoints

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use React for the frontend and explicit Laravel JSON endpoints for server communication.

### Context

The assignment PDF requires Livewire and/or React, so React independently satisfies the frontend framework requirement. The form builder needs drag-and-drop, deeply nested editing, immediate previews, raw JSON synchronization, and later conditional-logic tooling.

### Alternatives considered

- Option A: React frontend with Laravel JSON endpoints.
- Option B: Livewire with limited Alpine.js and ES6 responsibilities.
- Option C: React only for the builder while using Blade/Livewire elsewhere.

### Rationale

React provides the clearest interaction model for a stateful form-building application. Explicit JSON endpoints keep Laravel authoritative for authentication, authorization, schema validation, persistence, queues, imports, and submissions without coupling domain behavior to frontend components.

### Accepted trade-offs

- The project needs an explicit client/server contract and frontend test strategy.
- Schema validation must not be duplicated inconsistently between React and Laravel; Laravel remains authoritative before persistence.
- Blade remains required by the assignment and will provide the Laravel-hosted application entry shell and appropriate server-rendered views, but it will not own builder state.

### Consequences

- React owns the in-memory working schema and ephemeral builder interactions.
- Laravel owns persisted schema state and validates every write through JSON endpoints.
- Livewire and Alpine.js are not required unless a later approved need emerges.
- The exact React build tooling, routing, authentication transport, data-fetching approach, and drag-and-drop library remain separate Milestone 0 decisions.

### Follow-up decisions

- React and frontend build approach.
- Authentication starter and transport approach.
- Client routing boundaries.
- Frontend testing tools.

## Decision 006: Laravel Vite React Integration

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — extend Laravel's existing Vite pipeline with React and retain Tailwind CSS 4.

### Context

Laravel 13 already provided Vite, `laravel-vite-plugin`, and Tailwind CSS 4 under `/src`. The approved React frontend needed a build integration without creating a second application or weakening the explicit Laravel JSON API boundary.

### Alternatives considered

- Option A: Add `@vitejs/plugin-react` to Laravel's Vite pipeline and mount React from Blade.
- Option B: Add Inertia.js and its Laravel/React adapters.
- Option C: Create and deploy a separate React application directory.

### Rationale

Option A preserves one build and deployment pipeline, uses the scaffolded Tailwind configuration, and supports the approved React/Laravel separation without adding Inertia or cross-origin deployment concerns.

### Accepted trade-offs

- React source and Laravel source share the `/src` service boundary.
- Blade provides the HTML entry shell while React owns the interactive application tree.
- Client routing, server route fallback behavior, authentication transport, data fetching, and frontend test tooling remain unapproved follow-up decisions.

### Consequences

- React, React DOM, and `@vitejs/plugin-react` are installed and locked by npm.
- Vite compiles `resources/js/app.jsx` with React Fast Refresh support during development.
- `resources/views/app.blade.php` provides the React mount point and CSRF metadata.
- No application route was changed because client/server routing remains a separate decision.

### Follow-up decisions

- Authentication starter and transport approach.
- Client routing boundaries.
- Frontend testing tools.

## Decision 007: Separate Nginx and PHP-FPM Containers

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — run Nginx and PHP-FPM as separate containers.

### Context

The Laravel service needs a local and deployable web-serving topology. The alternatives were separate Nginx/PHP-FPM services, a combined FrankenPHP service, or Apache with `mod_php`.

### Alternatives considered

- Option A: Separate Nginx and PHP-FPM containers.
- Option B: Use FrankenPHP as the combined PHP application server.
- Option C: Use Apache with `mod_php`.

### Rationale

Separate services provide conventional production behavior, clear process responsibilities, independently configurable request handling, and a topology familiar to Laravel reviewers.

### Accepted trade-offs

- Local orchestration needs at least two containers for the web application path.
- Nginx and PHP-FPM configuration must remain synchronized for document roots, forwarded headers, upload limits, and timeouts.
- More operational configuration is required than with a combined application server.

### Consequences

- Nginx will terminate HTTP traffic and serve public assets.
- Dynamic Laravel requests will be forwarded to PHP-FPM over the internal Docker network.
- PHP-FPM will not be exposed directly to the host or public network.
- Base images, image-tag pinning, PHP extensions, and runtime user permissions remain follow-up decisions and are not yet implemented.

### Follow-up decisions

- Container base-image families and version-pinning policy.
- Required PHP extensions and image build stages.
- Runtime user and filesystem-permission model.
- Local ports and Docker service naming.

## Decision 008: Official Debian PHP and Alpine Nginx Base Images

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Partially superseded by Decision 010; the Debian/Nginx image families remain approved, while PHP 8.3 was replaced by PHP 8.4
- Approved option: Option A — use the official PHP 8.3 FPM Bookworm image and official versioned Alpine Nginx image.

### Context

Decision 007 established separate Nginx and PHP-FPM containers. Laravel 13 requires PHP 8.3 or newer, while the application will later need native extensions for MySQL, Redis, document parsing, spreadsheets, ZIP archives, and images.

### Alternatives considered

- Option A: Debian Bookworm PHP 8.3 FPM plus versioned Alpine Nginx.
- Option B: Alpine-based images for both PHP-FPM and Nginx.
- Option C: Custom images built from a general Ubuntu base.

### Rationale

The official Debian PHP image provides predictable native-extension compatibility and familiar debugging tools. Nginx has a much smaller dependency surface and benefits from the official Alpine variant without imposing musl-related PHP build complexity.

### Accepted trade-offs

- The PHP runtime image is larger than an Alpine equivalent.
- Two Linux distribution families must be scanned and maintained.
- Version-family tags receive upstream patch updates; immutable production releases should additionally capture image digests.

### Consequences

- The PHP image starts from `php:8.3-fpm-bookworm`.
- The Nginx image starts from `nginx:1.28-alpine`.
- Production release documentation will record resolved image digests.
- PHP extensions, multi-stage build contents, runtime permissions, Nginx routing, and Compose service names remain follow-up decisions.

### Follow-up decisions

- Required PHP extensions and multi-stage build design.
- Runtime user and filesystem-permission model.
- Nginx configuration and security headers.
- Local ports and Docker service naming.

## Decision 009: Explicit PHP Extensions and Multi-Stage Runtime

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use official PHP extension helpers with Composer-backed development and production targets.

### Context

The Laravel application requires native support for MySQL, queue workers, multibyte form content, localization, ZIP-based DOCX/XLSX formats, image processing, numeric operations, and production bytecode caching.

### Alternatives considered

- Option A: Use official `docker-php-ext-*` helpers, an explicit extension list, a Composer stage, and separate development/production targets.
- Option B: Use the third-party `install-php-extensions` helper and one general-purpose target.
- Option C: Install only immediately needed extensions and repeatedly expand the image later.

### Rationale

Option A keeps native build behavior explicit and auditable, avoids executing a third-party privileged installer, and prevents development Composer packages from entering the production target.

### Accepted trade-offs

- The Dockerfile is longer and retains Debian development libraries until a later image-size optimization pass.
- Native extensions increase build time.
- Redis support is intentionally deferred to the separately approved Redis-client decision.

### Consequences

- The PHP base installs `pdo_mysql`, `bcmath`, `intl`, `mbstring`, `zip`, `gd`, `pcntl`, and `opcache`.
- Composer is copied from the official Composer image.
- The development target provides the application runtime without copying host source or dependencies.
- The production target installs locked non-development Composer dependencies, copies application source, creates an authoritative autoloader, and assigns Laravel writable directories to `www-data`.
- Docker build context excludes local secrets, host dependencies, runtime caches, and Python virtual environments.

### Follow-up decisions

- Native PhpRedis versus Composer-based Predis.
- Runtime user and development bind-mount permission model.
- Frontend asset production stage and Nginx packaging.

## Decision 010: Align the Runtime and Lock File on PHP 8.4

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — upgrade the application runtime to PHP 8.4 and verify the lock file on that runtime.

### Context

The Laravel 13 lock file was initially resolved by the official Composer image on a newer PHP runtime. It selected Symfony 8.1 packages requiring PHP 8.4.1 or newer, so the approved PHP 8.3 production image could compile extensions but could not install the locked dependency graph.

### Alternatives considered

- Option A: Upgrade the application runtime to PHP 8.4 and validate the existing dependency family naturally.
- Option B: Keep PHP 8.3 and force Composer's platform setting to resolve older compatible transitive dependencies.
- Option C: Ignore Composer platform requirements during production builds.

### Rationale

PHP 8.4 satisfies the assignment's PHP 8.2+ requirement, matches the securely resolved Laravel 13 dependency graph, and preserves Composer compatibility enforcement without artificial platform emulation.

### Accepted trade-offs

- Decision 008's PHP 8.3 tag is superseded.
- Deployment environments must support PHP 8.4 rather than Laravel 13's nominal PHP 8.3 minimum.
- Native PHP extensions must be rebuilt for PHP 8.4.

### Consequences

- PHP images use `php:8.4-fpm-bookworm`.
- Composer lock validation and production installation run on PHP 8.4.
- Ignoring platform requirements remains prohibited.

### Follow-up decisions

- Native PhpRedis versus Composer-based Predis.
- Runtime user and development bind-mount permission model.
- Frontend asset production stage and Nginx packaging.

## Decision 011: Native PhpRedis Client

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use the native PhpRedis extension and pin a PHP 8.4-compatible stable release.

### Context

Redis will support Laravel queues, Horizon, caching, sessions, and rate limiting. Laravel can communicate with Redis through the native PhpRedis extension, the Composer-based Predis client, or less conventional alternatives such as Relay.

### Alternatives considered

- Option A: Native PhpRedis installed through PECL.
- Option B: Pure-PHP Predis installed through Composer.
- Option C: Relay.

### Rationale

PhpRedis provides conventional Laravel integration with lower CPU and memory overhead for queue and cache workloads. PECL lists `6.3.0` as a stable release compatible with PHP 7.4 and newer, including the project's PHP 8.4 runtime.

### Accepted trade-offs

- The PHP image compiles and maintains one additional native extension.
- PhpRedis version upgrades require rebuilding and testing the PHP images.
- The application is less portable to PHP runtimes where native extensions cannot be installed.

### Consequences

- Both PHP image targets enable pinned PhpRedis `6.3.0`.
- Laravel's Redis client configuration will use `phpredis`.
- Redis server topology, persistence, health checks, Horizon processes, and queue policies remain separate decisions.

### Follow-up decisions

- Redis server and Horizon process layout.
- Runtime user and development bind-mount permission model.
- Local Docker service names and ports.

## Decision 012: Shared Redis Service with Dedicated Horizon Process

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use one Redis service and run Horizon in a dedicated container based on the production PHP image.

### Context

Redis will back queues, cache, sessions, rate limiting, and Horizon metadata. Queue processing needs an observable, independently restartable process without duplicating the Laravel runtime image.

### Alternatives considered

- Option A: One Redis service with a dedicated Horizon container.
- Option B: Separate Redis instances for queue and cache/session workloads.
- Option C: One Redis service with ordinary Laravel queue workers instead of Horizon.

### Rationale

One Redis service is operationally appropriate for the expected assignment/demo scale, while a dedicated Horizon process provides queue metrics, balancing, monitoring, and an independent lifecycle using the same tested application image.

### Accepted trade-offs

- Redis is a shared failure and resource domain for queues, cache, sessions, and rate limiting.
- Logical prefixes and database numbers provide namespace separation but not infrastructure isolation.
- Production scaling may eventually justify dedicated Redis instances for queue and cache workloads.

### Consequences

- Laravel Horizon `5.48.1` is installed and locked.
- Horizon configuration and its service provider are published in the Laravel application.
- The PHP Dockerfile exposes a dedicated `horizon` target whose command is `php artisan horizon`.
- The Horizon process uses the same code, extensions, and production dependencies as PHP-FPM.
- Redis image/version, persistence, health checks, service names, and Horizon tuning remain follow-up decisions.

### Follow-up decisions

- Redis server version, persistence, and health-check policy.
- Horizon supervisor counts, balancing, retry, timeout, and backoff settings.
- Horizon dashboard authorization.
- Local Docker service names and ports.

## Decision 013: Persistent Internal Redis 8 Service

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use Redis 8 Alpine with AOF persistence, a named volume, and an internal health check.

### Context

The shared Redis service will hold queued jobs, sessions, cache entries, rate-limit state, and Horizon metadata. Container recreation must not silently discard queued work, and dependent services need an objective readiness signal.

### Alternatives considered

- Option A: Redis 8 Alpine with append-only persistence, a named volume, and health checks.
- Option B: Redis 8 Alpine without persistence.
- Option C: Redis 7.2 Alpine with persistence.

### Rationale

Redis 8 provides the current server generation, AOF with one-second fsync balances durability and throughput for the expected workload, and `redis-cli ping` provides a direct health signal for Docker dependencies.

### Accepted trade-offs

- Local Redis state survives ordinary container recreation and requires deliberate volume cleanup when a clean environment is desired.
- `appendfsync everysec` can lose approximately one second of acknowledged writes during a host-level crash.
- Redis accepts unauthenticated traffic on its container interface, so it must remain on the private Docker network with no published host port; production credentials and network policy must be revisited during deployment hardening.
- Redis 8 licensing must be documented with deployment dependencies.

### Consequences

- The Redis image starts from `redis:8-alpine`.
- AOF and periodic snapshots are enabled under `/data`.
- Docker health checks execute `redis-cli ping`.
- Compose will attach `/data` to a named volume and will not publish port 6379 to the host.
- PHP-FPM and Horizon must wait for Redis health before starting Redis-dependent behavior.

### Follow-up decisions

- Local Docker service names, network, and ports.
- Redis authentication and secret delivery for production.
- Horizon supervisor counts and queue policies.

## Decision 014: Private Local Docker Network and Minimal Host Exposure

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use one private Docker network, conventional service names, and publish only Nginx on port 8080.

### Context

The local stack will include Nginx, PHP-FPM, Horizon, MySQL, Redis, and FastAPI. Docker service names become internal DNS contracts used by Laravel, Nginx, workers, and health checks, while published ports affect host conflicts and local attack surface.

### Alternatives considered

- Option A: One private network; publish only Nginx on port 8080.
- Option B: One private network while also publishing MySQL, Redis, and FastAPI ports to localhost.
- Option C: Split services across frontend, application, and data networks.

### Rationale

A single private network is sufficient for assignment-scale isolation and keeps service discovery straightforward. Publishing only Nginx mirrors production ingress and avoids unnecessary database, Redis, PHP-FPM, and internal AI-service exposure.

### Accepted trade-offs

- Direct desktop access to MySQL or Redis requires `docker compose exec` or explicitly approved temporary tooling.
- All internal services share one Docker network rather than enforcing tier-specific network segmentation.
- Vite development exposure remains part of the supported local-development workflow decision.

### Consequences

- Internal service names are `nginx`, `php`, `horizon`, `mysql`, `redis`, and `ai-service`.
- The private network will be named `formforge` within Compose.
- Nginx will publish host port `8080`; PHP-FPM, MySQL, Redis, and FastAPI will publish no host ports.
- Laravel connection hosts will use `mysql`, `redis`, and `ai-service`.
- The Compose file will be created after its remaining service-specific decisions are approved.

### Follow-up decisions

- MySQL image, persistence, credentials, and health check.
- Nginx routing and frontend asset packaging.
- Runtime user and development bind-mount permissions.
- FastAPI packaging and service health check.
- Supported Vite development workflow.

## Decision 015: MySQL 8.4 LTS with Persistent UTF-8 Storage

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use MySQL 8.4 LTS with a named volume, `utf8mb4`, health checks, and environment-supplied credentials.

### Context

The assignment requires MySQL 8 with migrations and seeders. The local and production-like stack needs durable data, international form content, an objective readiness signal, and credentials that are never committed.

### Alternatives considered

- Option A: MySQL 8.4 LTS with persistent storage and health checks.
- Option B: MySQL 8.0 with the same persistence and health policy.
- Option C: MariaDB as a compatible substitute.

### Rationale

MySQL 8.4 is the current LTS line, satisfies the MySQL 8 requirement, and provides a longer support horizon than MySQL 8.0. `utf8mb4` supports the multilingual labels and submissions required by AI translation and public forms.

### Accepted trade-offs

- Behavior must be tested against MySQL 8.4 rather than assuming exact MySQL 8.0 defaults.
- Durable local state requires explicit volume cleanup when a clean database is desired.
- Database credentials must be provided before Compose can start; no functional password defaults will be committed.

### Consequences

- The database image starts from `mysql:8.4`.
- Server and client defaults use `utf8mb4` with `utf8mb4_0900_ai_ci` collation.
- Docker health checks use `mysqladmin ping` on the container interface.
- Compose will attach `/var/lib/mysql` to a named volume and keep port 3306 private.
- Root, database, and application-user credentials will come from environment variables.

### Follow-up decisions

- Root versus service-specific environment-file organization.
- Development credential generation and onboarding workflow.
- Runtime user and bind-mount permissions.
- Final Compose assembly.

## Decision 016: Root Orchestration Environment with Scoped Service Templates

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use a root Compose environment and service-specific standalone templates with least-privilege delivery.

### Context

Docker Compose must coordinate shared database credentials, Laravel configuration, Redis hosts, and Laravel-to-FastAPI authentication. Laravel and FastAPI should also remain understandable and runnable independently without mounting one global secret file into every service.

### Alternatives considered

- Option A: Root Compose `.env` plus service-specific `.env.example` files, with Compose passing only required variables.
- Option B: Completely separate environment files for every service and Compose.
- Option C: Mount one root environment file wholesale into every container.

### Rationale

Option A gives reviewers one Docker onboarding surface while preserving explicit standalone service documentation and preventing unrelated secrets from reaching every container.

### Accepted trade-offs

- Some variable names are intentionally duplicated between orchestration and service templates.
- Compose environment mappings must be maintained when variables are added or renamed.
- Required passwords and service secrets are blank in examples, so onboarding must generate or request them before startup.

### Consequences

- Root `.env.example` documents Compose and cross-service variables.
- Laravel's `.env.example` uses the approved `mysql`, `redis`, and `ai-service` internal hosts.
- FastAPI has a minimal standalone `.env.example` without committing packaging decisions.
- Real root and service environment files remain ignored; example templates remain trackable.
- Compose will pass variables explicitly instead of using a wholesale `env_file` for every container.

### Follow-up decisions

- Development credential generation and onboarding workflow.
- FastAPI packaging and configuration model.
- Final Compose variable mapping.

## Decision 017: Python 3.12 FastAPI Service Managed by uv

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use Python 3.12, `pyproject.toml`, and a deterministic `uv.lock` managed by uv.

### Context

The internal AI service needs reproducible runtime and development dependencies for FastAPI, Pydantic validation, provider HTTP calls, testing, linting, and static analysis.

### Alternatives considered

- Option A: `pyproject.toml` and `uv.lock` managed by uv.
- Option B: Poetry with `poetry.lock`.
- Option C: pip with `requirements.txt` files.

### Rationale

uv provides fast deterministic locking, standards-based project metadata, dependency groups, and a small Docker/CI command surface. The official uv image also supplies the approved Python 3.12 runtime.

### Accepted trade-offs

- Contributors running the service outside Docker need uv installed.
- uv is a newer tool than pip or Poetry and must be documented.
- The toolchain image is pinned and requires deliberate upgrades.

### Consequences

- Runtime dependencies include FastAPI, HTTPX, Pydantic Settings, and Uvicorn.
- Development dependencies include Pytest, Ruff, and Mypy.
- The service exposes the planned `/health` endpoint and has a baseline endpoint test.
- The Docker image pins uv `0.9.30` with Python 3.12 on Debian Bookworm.
- Provider adapters, internal authentication, structured-output contracts, retries, and logging remain later decisions.

### Follow-up decisions

- Internal FastAPI authentication.
- FastAPI configuration validation and secret requirements.
- Provider interface and initial provider.
- Service health-check and production worker policy.

## Decision 018: HMAC-Signed Laravel-to-FastAPI Requests

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — authenticate internal AI requests with HMAC-SHA256 signatures and bounded timestamps.

### Context

AI prompts and existing form schemas can contain sensitive user-authored data. The private Docker network limits exposure but does not provide application-layer service identity, request-integrity protection, or replay resistance.

### Alternatives considered

- Option A: HMAC-SHA256 over timestamp, method, path, and body hash.
- Option B: Shared bearer secret.
- Option C: Mutual TLS.

### Rationale

HMAC provides request integrity and service authentication without certificate infrastructure. A timestamp window limits captured-request replay, and constant-time comparison avoids leaking signature information through timing differences.

### Accepted trade-offs

- Laravel and FastAPI must implement an identical canonical request format.
- Container clocks must remain synchronized within the configured five-minute default window.
- Timestamp validation reduces replay exposure but does not provide one-time nonce storage within the accepted window.

### Consequences

- Protected `/v1/*` requests use `X-FormForge-Timestamp` and `X-FormForge-Signature` headers.
- The canonical payload is timestamp, uppercase HTTP method, URL path, and lowercase SHA-256 body hash separated by newline characters.
- The default maximum clock skew is 300 seconds and is configurable.
- FastAPI fails closed with HTTP 503 if its secret is unset and HTTP 401 for missing, stale, tampered, or invalid signatures.
- `/health` remains unauthenticated for container orchestration.
- Laravel has a signer service; the later AI HTTP client must use it for every protected request.

### Follow-up decisions

- Provider interface and initial provider.
- Laravel AI HTTP timeout and retry behavior.
- Secret generation and production delivery.

## Decision 019: Non-Root Runtime with Host-Matched Development Identity

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use `www-data` in production and configurable host UID/GID values for development bind mounts.

### Context

Development will bind-mount Laravel source from the host, while production images contain immutable application source. A fixed container identity can create root-owned or unwritable files on Linux hosts, but running application processes as root weakens container isolation.

### Alternatives considered

- Option A: Non-root production processes and configurable host-matched development UID/GID values.
- Option B: Run PHP containers as root in all environments.
- Option C: Use the image's fixed `www-data` numeric identity everywhere.

### Rationale

Option A preserves a non-root application runtime while allowing Linux contributors to match host ownership. Docker Desktop users can retain the documented defaults because bind-mount ownership is mediated by the VM layer.

### Accepted trade-offs

- Development builds vary when `HOST_UID` or `HOST_GID` changes.
- UID/GID defaults may need adjustment on Linux hosts.
- The PHP-FPM master retains the official image's startup model while its request workers run as `www-data`; Horizon runs entirely as `www-data`.

### Consequences

- Development image build arguments are `APP_UID` and `APP_GID`, sourced later from `HOST_UID` and `HOST_GID` in Compose.
- Development application commands run as the remapped `www-data` identity.
- Horizon runs as `www-data`.
- Production writable directories remain owned by `www-data`.
- Compose will bind-mount `/src`, while dependency and mutable runtime paths use Docker-managed volumes to avoid polluting or conflicting with the host filesystem.

### Follow-up decisions

- Exact development volume layout and initialization command.
- Nginx routing and shared public-asset mount.
- Supported Vite development workflow.

## Decision 020: Nginx Static Serving with Laravel Front-Controller Routing

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — serve Laravel public assets through Nginx and forward only the front controller to PHP-FPM.

### Context

The approved topology separates Nginx and PHP-FPM while React and Laravel share one application origin. Nginx needs explicit rules for static assets, Laravel routes, React routes handled through Laravel, and PHP execution boundaries.

### Alternatives considered

- Option A: Nginx serves `/public`, forwards only `index.php`, and shares a read-only development source view.
- Option B: A separate frontend server hosts React while Nginx proxies Laravel APIs.
- Option C: Replace the approved topology with a combined HTTP-capable PHP server.

### Rationale

The Laravel front-controller pattern keeps one origin and avoids CORS complexity. Restricting FastCGI forwarding to `index.php` prevents arbitrary uploaded or accidentally exposed PHP files from being executed.

### Accepted trade-offs

- Nginx needs read access to the Laravel application tree during local development, though its mount remains read-only.
- React client routes require a later Laravel fallback route after routing behavior is approved.
- Immutable static caching assumes Vite's content-hashed production assets; non-hashed public assets require deliberate cache naming or later rule refinement.

### Consequences

- Nginx document root is `/var/www/html/public`.
- Existing static assets are served directly and missing static assets return 404.
- All non-file requests fall back to Laravel's `index.php`.
- Only `/index.php` is passed to the internal `php:9000` upstream.
- Hidden paths are denied except the conventional `.well-known` namespace.
- Development Compose will mount `/src` read/write into PHP and read-only into Nginx.

### Follow-up decisions

- React/Laravel route ownership and client-side fallback.
- Production frontend asset build and packaging.
- Upload-size and FastCGI timeout limits.
- Final Compose volume assembly.

## Decision 021: One-Shot Development Application Initialization

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — initialize managed Laravel volumes in a one-shot Compose service before PHP-FPM and Horizon.

### Context

The approved non-root development runtime cannot reliably write Laravel runtime directories exposed directly through cross-platform bind mounts. Composer dependencies and mutable framework state should not pollute the host or require manual permission fixes.

### Alternatives considered

- Option A: One-shot `app-init` service prepares managed volumes and installs locked dependencies.
- Option B: Start PHP-FPM as root, repair permissions on every startup, then drop privileges.
- Option C: Keep mutable directories on the host and document manual permission repair.

### Rationale

A one-shot initializer isolates privileged ownership changes from long-running services. PHP-FPM and Horizon remain non-root and can depend on a deterministic successful initialization result.

### Accepted trade-offs

- Compose gains an additional short-lived service.
- Composer validates the lock and installed dependencies on each explicit initialization run, though cached volumes make subsequent runs inexpensive.
- Resetting dependencies or runtime data requires deliberate named-volume removal.

### Consequences

- The PHP Dockerfile has an `app-init` target that runs as root only for directory creation and ownership assignment.
- Composer itself runs as the remapped `www-data` user.
- Managed volumes will cover `/var/www/html/vendor`, `/var/www/html/storage`, and `/var/www/html/bootstrap/cache`.
- The initializer creates Laravel's required storage subdirectories before package discovery.
- PHP-FPM and Horizon will use `condition: service_completed_successfully` in Compose.

### Follow-up decisions

- Node dependency and Vite development volume/workflow.
- Final Compose volume declarations and startup command.
- Clean-reset and onboarding commands.

## Decision 022: Dockerized Node and Vite Development Workflow

- Date: 2026-08-04
- Milestone: 0 — Repository Bootstrap and Guardrails
- Status: Approved
- Approved option: Option A — use dedicated Node 24 initialization and Vite development containers with a managed dependency volume.

### Context

The React frontend requires deterministic npm installation, Vite builds, and browser-accessible hot-module replacement without requiring reviewers to install Node on the host.

### Alternatives considered

- Option A: Dedicated Node container, managed `node_modules`, and development-only Vite port 5173.
- Option B: Run Node and Vite directly on the host.
- Option C: Build static assets only, without a Vite development server.

### Rationale

A containerized Node toolchain makes onboarding reproducible and keeps host Node versions and native packages out of the critical path. A managed dependency volume avoids cross-platform `node_modules` incompatibilities.

### Accepted trade-offs

- Development runs an additional long-lived container and one short-lived frontend initializer.
- Port 5173 is a deliberate development-only exception to Decision 014's Nginx-only host exposure.
- File-watching performance depends on Docker Desktop bind-mount behavior on Windows and macOS.

### Consequences

- Node uses the official `node:24-bookworm-slim` image.
- Frontend dependencies are installed with `npm ci` into a managed `node_modules` volume.
- The Node identity uses the approved configurable host UID/GID values.
- Vite binds to `0.0.0.0:5173`, uses a configurable HMR host, and refuses silent port fallback.
- Laravel and Vite share `storage/framework/vite.hot` through the managed Laravel storage volume so the non-root Node process does not need to write into the bind-mounted `public` directory.
- Compose will publish Vite port 5173 only for the development workflow.

## Decision 023: Default Docker Compose Development Stack

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — one default Compose command starts the complete development environment; production-specific orchestration will be added separately later.

**Context**

This assignment is intended to be simple for a reviewer to run. Requiring an override file or knowledge of Compose profiles would add setup choices before the application can be evaluated.

**Options considered**

- Option A: A single default development stack, with a production override added only when needed.
- Option B: A base Compose file plus a separate development override.
- Option C: One Compose file with selectively enabled development profiles.

**Rationale**

Option A makes `docker compose up --build` the canonical local startup command. One-shot PHP and frontend initialization services populate managed dependency volumes before the long-running services start. Health-gated dependencies keep Laravel and Horizon from racing MySQL, Redis, or the AI service.

**Consequences**

- The default stack includes Nginx, PHP-FPM, Horizon, MySQL, Redis, FastAPI, and Vite.
- Only Nginx port 8080 and the development-only Vite port 5173 are published.
- Application dependencies and persistent data use named volumes; source code is bind-mounted read-only into runtime services.
- MySQL, Redis, and the AI service remain reachable only on the private Compose network.
- Reviewers must copy `.env.example` to `.env` and fill the explicitly required development secrets before startup.
- Production orchestration remains a later, explicit hardening task.

## Decision 024: Headless Authentication Foundation

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — Laravel Fortify as the headless authentication backend, session-cookie authentication, and custom React authentication screens.

**Context**

The approved frontend architecture uses React with Laravel JSON endpoints. An Inertia-based starter kit would change that boundary, while implementing password authentication manually would duplicate security-sensitive framework behavior.

**Options considered**

- Option A: Headless Fortify endpoints with Laravel sessions and custom React screens.
- Option B: Laravel's official React starter kit with Inertia.
- Option C: Manually implemented authentication endpoints.

**Rationale**

Fortify is Laravel's maintained, frontend-agnostic authentication backend and its current release supports Laravel 13. It provides throttled authentication and user-creation workflows while allowing React to remain responsible for the interface.

**Consequences**

- Authentication uses the existing `web` guard, server-side sessions, and CSRF protection.
- Fortify's server-rendered views are disabled.
- Registration, login, logout, and password-reset backend support are enabled.
- Advanced profile management, email verification, two-factor authentication, and passkeys remain disabled until their complete UX and security flows are designed.
- Sanctum is not installed because no token-authenticated public API has been approved.
- Inertia is not introduced; React continues to call Laravel endpoints directly.

## Decision 025: Parallel Continuous Integration

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — parallel GitHub Actions jobs for PHP, React, Python, and Docker validation, with MySQL and Redis service containers supporting Laravel checks.

**Context**

The repository contains three language/toolchain boundaries plus container orchestration. A single sequential job would obscure which boundary failed and would make unrelated checks wait for one another.

**Options considered**

- Option A: Parallel PHP, React, Python, and Docker validation jobs.
- Option B: One sequential job containing every check.
- Option C: Language tests without Docker build validation.

**Rationale**

Separate jobs provide fast, clearly attributed feedback while still requiring every repository boundary to pass. The PHP job also proves real MySQL and Redis connectivity before running the isolated test suite.

**Consequences**

- PHP CI installs locked dependencies, audits them, checks Pint formatting, migrates MySQL, pings Redis, and runs Laravel tests on PHP 8.4.
- React CI performs a clean npm install and production Vite build on Node 24.
- Python CI uses the pinned uv version and lockfile, then runs Ruff, strict Mypy, and Pytest on Python 3.12.
- Docker CI validates the Compose model and builds every service image.
- Jobs run concurrently and superseded branch runs are cancelled.
- The workflow has read-only repository permissions and uses non-production CI-only credentials.
- Additional PHP static analysis, Pest, and ESLint gates will be added when their Milestone 0 tooling is installed.

## Decision 026: PHP and JavaScript Quality Tooling

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — Pest 4, Larastan 3/PHPStan, and ESLint flat configuration, with the baseline PHP tests migrated to Pest and every check enforced in CI.

**Context**

Milestone 0 requires executable quality gates for both Laravel and React. The scaffold initially supplied PHPUnit and Pint but lacked Laravel-aware static analysis, Pest-style tests, and JavaScript linting.

**Options considered**

- Option A: Add Pest, Larastan/PHPStan, and ESLint; migrate baseline tests and enforce all checks in CI.
- Option B: Add Larastan and ESLint while retaining PHPUnit-style tests.
- Option C: Retain only Pint, PHPUnit, and frontend compilation.

**Rationale**

Pest 4 builds on the project's existing PHPUnit 12 runtime while providing the assignment's requested test interface. Larastan 3 supports Laravel 13 and adds framework-aware analysis. ESLint's current flat configuration format supports explicit React Hooks and Fast Refresh rules without a legacy configuration layer.

**Consequences**

- PHP tests use Pest syntax and remain compatible with Laravel's testing foundation.
- Larastan analyses application code at level 6 without a suppression baseline.
- ESLint checks JavaScript and JSX using recommended JavaScript, React Hooks, and Vite Fast Refresh rules.
- Composer and npm expose stable `analyse`, `test`, and `lint` commands.
- CI fails on PHP analysis errors, JavaScript lint errors, or warnings.

## Decision 027: Import, Schema, and Drag-and-Drop Libraries

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — PHPWord, PhpSpreadsheet, Opis JSON Schema, and SortableJS, matching the project plan.

**Context**

Later milestones require Word and spreadsheet import, server-side JSON Schema validation, and accessible builder reordering. Installing and locking the foundation libraries during bootstrap prevents environment and platform-extension surprises during feature work.

**Options considered**

- Option A: Use the package set named in the project plan.
- Option B: Select alternative document, schema, and drag-and-drop libraries and revise the plan.
- Option C: Defer dependency installation until the feature milestones.

**Rationale**

PHPWord and PhpSpreadsheet directly cover the required `.docx` and `.xlsx` formats. Opis provides maintained JSON Schema validation in PHP. SortableJS supplies a focused, framework-independent drag-and-drop engine that can be wrapped by React components without coupling builder state to a UI framework.

**Consequences**

- Production dependencies are locked to PHPWord 1.4, PhpSpreadsheet 5.9, Opis JSON Schema 2.6, and SortableJS 1.15.
- PHP container images and CI include DOM, SimpleXML, XML, XMLReader, and XMLWriter support.
- Baseline tests prove the PHP libraries can construct documents/workbooks and validate JSON data.
- A frontend dependency check proves the locked SortableJS module and factory API can load before linting and builds.
- Actual import policy, schema structure, and builder integration remain decisions for their respective milestones.

## Decision 028: Laravel Liveness, Readiness, and Queue Smoke Checks

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — retain `/up` for shallow liveness, add `/health` readiness checks for MySQL and Redis, and provide a separate deterministic Horizon queue smoke command.

**Context**

Process liveness, dependency readiness, and asynchronous queue processing answer different operational questions. Combining all three in one HTTP request would make routine probes slow and would continuously enqueue work.

**Options considered**

- Option A: Separate shallow liveness, dependency readiness, and explicit queue round-trip checks.
- Option B: A shallow `/health` endpoint plus a separate queue smoke command.
- Option C: Perform a queued-job round trip on every `/health` request.

**Rationale**

The approved split keeps liveness cheap, makes readiness accurately reflect MySQL and Redis availability, and reserves the more expensive Horizon verification for startup acceptance and diagnostics.

**Consequences**

- `/up` remains Laravel's framework-managed liveness endpoint.
- `/health` returns HTTP 200 only when both MySQL and Redis respond; otherwise it returns HTTP 503 with component status but no exception details.
- Nginx container health uses the readiness endpoint.
- `php artisan queue:smoke` dispatches a unique job and waits up to 15 seconds by default for a Redis completion marker.
- Queue smoke timeouts are bounded to 1–60 seconds and completion markers expire after 60 seconds.
- The queue round trip is run explicitly during integration checks rather than on every health request.

## Decision 029: Reviewer Documentation and Repository Skeleton

**Status:** Approved and implemented during Milestone 0.

**Approved option:** Option A — one reviewer-first root README, removal of Laravel's generic scaffold README, and documented placeholders for shared contracts and import samples.

**Context**

This is a one-time assignment repository. Reviewers need one authoritative entry point describing the actual architecture, setup commands, checks, and current limitations. Retaining Laravel's generic framework README would create a competing and inaccurate starting point.

**Options considered**

- Option A: Create a comprehensive root README, remove the generic Laravel README, and add planned skeleton directories.
- Option B: Add a root README while retaining the generic Laravel README and deferring skeleton directories.
- Option C: Keep only minimal setup documentation.

**Rationale**

A single root document minimizes reviewer setup effort and makes operational constraints visible before startup. Documented placeholders preserve the approved repository shape without pretending that Milestone 1 contracts or later import fixtures already exist.

**Consequences**

- The root README is the canonical setup and project overview.
- Quick start explicitly requires local secrets, Compose startup, and database migration.
- Verified health, queue, test, analysis, lint, and build commands are documented.
- The generic Laravel scaffold README is removed.
- `contracts/` and `samples/imports/` are tracked with scope-specific placeholder documentation.
- Product-domain artifacts remain deferred until their decisions are approved.

### Follow-up decisions

- Production frontend asset build and packaging.
- Final Compose services, profiles, volumes, and dependencies.
- Clean-reset and onboarding commands.

## Decision 030: Membership-Only Multi-Tenant Ownership

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; implementation pending dependent identifier and role decisions.

**Approved option:** Option A - model tenants and users as a many-to-many relationship through `tenant_user`, create a personal tenant and initial membership during registration, and derive tenant authority exclusively from membership rather than a separate `owner_user_id`.

**Context**

Milestone 0 established session-authenticated users, but the application has no tenant domain or active-tenant context. Every protected form-domain record must eventually be tenant-scoped, and a user may belong to more than one tenant.

**Options considered**

- Option A: Many-to-many tenant membership without a separate tenant owner foreign key.
- Option B: Store one `tenant_id` directly on each user.
- Option C: Many-to-many membership plus a separate `owner_user_id` on tenants.

**Rationale**

A membership-only relationship supports users belonging to multiple tenants and keeps authorization based on one consistent source. It avoids the contradictory states that can arise when a tenant owner foreign key and membership roles are maintained separately.

**Accepted trade-offs**

- Every authenticated tenant-scoped operation must resolve and validate an active membership.
- Registration must transactionally create both a personal tenant and its initial membership.
- Tenant switching and future invitations require explicit membership-aware workflows.

**Consequences**

- The domain will contain `tenants` and a unique `tenant_user` membership table.
- `User` and `Tenant` will expose many-to-many relationships.
- A newly registered user will receive a personal tenant and initial membership in one transaction.
- Tenant authorization will not depend on a separate `owner_user_id` column.
- Identifier types, initial role value and permissions, tenant slug generation, and active-tenant storage remain separate decisions.

**Follow-up decisions**

- Role model and permission boundaries.
- Primary-key and public-identifier strategy.
- Tenant slug generation and active-tenant selection behavior.
- Queue tenant-context restoration.

## Decision 031: Fixed Owner, Editor, and Viewer Roles

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; role semantics implemented, with persistence and policies pending dependent domain decisions.

**Approved option:** Option A - use the fixed `owner`, `editor`, and `viewer` tenant roles.

**Context**

The approved membership-only tenancy model requires every tenant membership to carry an authorization role. The application needs useful least-privilege boundaries without the complexity of a configurable permissions subsystem.

**Options considered**

- Option A: Fixed owner, editor, and viewer roles.
- Option B: Fixed owner and member roles.
- Option C: Configurable roles and granular permission records.

**Rationale**

Three fixed roles provide clear administrative, editing, and read-only boundaries while remaining small enough to express through Laravel policies and test exhaustively.

**Accepted trade-offs**

- Tenants cannot define custom roles or permissions.
- Editors have one consistent product-resource permission set rather than per-feature grants.
- Viewer access includes reading forms and submissions and exporting submission data.

**Consequences**

- Owners may administer tenant membership and perform all product-resource operations.
- Editors may create and manage forms, versions, submissions, imports, and AI requests but may not administer the tenant or memberships.
- Viewers may read forms and submissions and export data but may not mutate tenant resources.
- New registrations receive an owner membership in their personal tenant.
- Authorization policies must deny unknown role values and test the complete role matrix.

**Follow-up decisions**

- Primary-key and public-identifier strategy.
- Active-tenant selection and context storage.
- Concrete policies as tenant-owned resources are introduced.

## Decision 032: Bigint Internal Keys with ULID Public Identifiers

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved and applied to the tenant and membership foundation; public identifiers will be added with externally addressable resources.

**Approved option:** Option A - retain unsigned bigint internal primary and foreign keys and add immutable ULID `public_id` values only to externally addressable domain records.

**Context**

The Laravel user and framework tables already use bigint primary keys. The tenant foundation and later form-domain migrations require a consistent relationship-key strategy, while public routes and asynchronous request polling must not expose sequential database identifiers.

**Options considered**

- Option A: Bigint internal keys with ULID public identifiers on externally addressable records.
- Option B: ULID primary keys on every domain table.
- Option C: Bigint internal keys with UUIDv7 public identifiers.

**Rationale**

Bigint keys keep MySQL joins, foreign keys, and composite indexes compact and remain consistent with users. ULIDs provide immutable, non-sequential, time-sortable public identifiers without enlarging every internal relationship.

**Accepted trade-offs**

- Externally addressable models have separate internal and public identities.
- Route binding and serialization must deliberately select the public identifier.
- Public identifiers reduce enumeration risk but never replace authorization.

**Consequences**

- Tenants, memberships, versions, and submission files use bigint internal relationships.
- Forms, submissions, AI requests, and imports receive unique 26-character ULID `public_id` columns when introduced.
- Tenants are presented through their approved slug strategy.
- Internal numeric identifiers must not appear in public product URLs or external API payloads.

**Follow-up decisions**

- Form slug and public URL uniqueness.
- Route model binding and serialization details when public endpoints are introduced.

## Decision 033: Immutable Scoped Slugs for Public Form URLs

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; tenant behavior implemented, with the form index and route pending the form-domain decisions.

**Approved option:** Option A - use globally unique tenant slugs and tenant-scoped form slugs, generate readable values with a short ULID-derived collision suffix, keep them immutable, and publish forms at `/f/{tenantSlug}/{formSlug}`.

**Context**

Registration must provision a personal tenant, and the planned public form route contains both tenant and form slugs. Their uniqueness and mutation rules must preserve links once forms are published.

**Options considered**

- Option A: Immutable readable slugs, scoped appropriately, with collision suffixes.
- Option B: Editable readable slugs plus redirect history.
- Option C: Public URLs containing only form ULIDs.

**Rationale**

Immutable scoped slugs keep published URLs readable and stable without introducing redirect infrastructure. A database unique constraint remains the authority, while a short ULID suffix resolves collisions safely.

**Accepted trade-offs**

- Renaming a tenant or form does not update its URL.
- Colliding names receive a less concise suffix.
- Slugs are identifiers and never substitute for publication checks or authorization.

**Consequences**

- Tenant slugs are globally unique and immutable.
- Form slugs will be unique by `(tenant_id, slug)` and immutable.
- Registration creates a readable personal-workspace tenant slug and retries collisions inside its transaction.
- Public form resolution will require both slug values and return only published forms.

**Follow-up decisions**

- Form/version table relationship.
- Public route binding and publication behavior.

## Decision 034: Separate Current and Published Form-Version Pointers

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; implementation pending immutable-version and schema-storage decisions.

**Approved option:** Option A - each version belongs to one form, while the form stores separate nullable `current_version_id` and `published_version_id` pointers.

**Context**

The builder must support revising a form that is already live without silently changing the schema respondents receive. Submissions must also retain the exact immutable version against which they were validated.

**Options considered**

- Option A: Separate current and published version pointers on the form.
- Option B: One current-version pointer used by both editing and public rendering.
- Option C: Current and published flags stored on version rows.

**Rationale**

Two explicit pointers make publication a transactionally controlled state change. Editors may create a newer current draft while public rendering continues to use the previously published version.

**Accepted trade-offs**

- Forms carry two nullable foreign keys and require explicit state invariants.
- Creating a form and its first version must be transactional because the current pointer is temporarily null.
- Publication and rollback services must update pointers consistently.

**Consequences**

- `form_versions.form_id` identifies version ownership.
- `forms.current_version_id` identifies the newest editor-visible version.
- `forms.published_version_id` identifies the only schema eligible for public rendering.
- Draft forms have no published pointer.
- Public submissions will reference the precise published version used for validation.

**Follow-up decisions**

- Immutable version behavior.
- Canonical JSON and checksum approach.
- JSON representation and size limits.
- Publication, unpublication, and rollback state transitions.

## Decision 035: Append-Only Transactional Form Versions

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; implementation pending canonical JSON, checksum, and schema-storage decisions.

**Approved option:** Option A - every meaningful schema save creates an immutable version through `FormVersionService`, with row locking, sequential numbering, checksum no-op detection, and transactional current-pointer updates.

**Context**

The approved dual-pointer model requires predictable version creation under concurrent edits and must preserve every schema that was previously current or published. The acceptance criteria also require identical saves to avoid duplicate versions.

**Options considered**

- Option A: Append-only versions enforced through a service, transaction, row lock, model guards, and restrictive foreign keys.
- Option B: Update the latest draft in place until it is published or superseded.
- Option C: Append-only versions enforced with MySQL database triggers.

**Rationale**

Append-only records provide complete history and clear rollback semantics. Locking the parent form serializes version-number allocation, while checksum comparison avoids inserting versions for semantically identical schemas.

**Accepted trade-offs**

- Meaningful edits create additional rows.
- Eloquent lifecycle guards do not protect against privileged raw SQL; application writes must use the version service.
- Concurrent saves serialize briefly on the form row.

**Consequences**

- `FormVersionService` is the only supported schema persistence boundary.
- It validates, normalizes, canonicalizes, and checksums before opening the write transaction.
- Inside the transaction it locks the form, returns the current version for a checksum match, or inserts the next sequential version and updates `current_version_id`.
- Version models reject updates and deletes.
- Foreign keys restrict deletion when a form pointer or submission references a version.
- Tests must cover no-op saves, immutability, rollback on failure, and concurrent numbering.

**Follow-up decisions**

- Canonical JSON and checksum approach.
- JSON column representation and schema limits.
- Exact foreign-key deletion behavior for form lifecycle operations.

## Decision 036: Project-Owned Canonical JSON with SHA-256 Checksums

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved and implemented.

**Approved option:** Option A - normalize first, recursively sort object keys, preserve list order, encode with stable JSON flags, and store a lowercase hexadecimal SHA-256 checksum.

**Context**

Append-only version saves must recognize schemas that differ only in formatting or object-key order while continuing to treat builder array order as meaningful.

**Options considered**

- Option A: A small project-owned canonicalizer tailored to the form schema.
- Option B: Full RFC 8785/JCS through another Composer dependency.
- Option C: Hash the incoming JSON encoding directly.

**Rationale**

Recursive object-key sorting and stable encoding cover the product's equality requirements without another dependency. Preserving list order ensures reordered steps, sections, fields, options, and conditions create new versions.

**Accepted trade-offs**

- The representation is project-specific rather than RFC 8785.
- Empty PHP arrays are treated as JSON lists; empty objects must be represented as objects before canonicalization if the schema introduces them.
- Checksums identify canonical content but are not cryptographic signatures.

**Consequences**

- Canonicalization rejects unsupported PHP values through throwing JSON encoding.
- Unicode and slashes remain unescaped, insignificant whitespace is removed, and fractional zeroes are preserved.
- Version checksums use lowercase SHA-256 hexadecimal strings suitable for `CHAR(64)` storage.
- Shared fixtures must prove compatible behavior in Laravel and FastAPI before cross-service persistence is enabled.

**Follow-up decisions**

- Form schema v1 structure and normalization defaults.
- JSON storage representation and size limits.

## Decision 037: Strict Fully Normalized Form Schema v1

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved and implemented at the shared-contract boundary; semantic validation remains pending its limits decision.

**Approved option:** Option A - use JSON Schema Draft 2020-12, require the complete v1 shape, reject unknown properties, and persist explicit defaults rather than sparse objects.

**Context**

Laravel, React, FastAPI, imports, and public submission validation need one unambiguous representation. Sparse or extensible objects would require each runtime to reproduce defaults identically before checksumming and persistence.

**Options considered**

- Option A: Strict closed objects with all normalized properties required.
- Option B: Sparse objects whose missing properties are filled by a normalizer.
- Option C: Permissive objects that preserve unknown properties.

**Rationale**

A fully explicit schema makes the shared JSON Schema the authoritative contract and removes defaulting drift across languages. Unknown client or AI output fails visibly.

**Accepted trade-offs**

- Persisted documents are more verbose.
- Contract evolution requires a new schema version or an explicit compatible migration.
- Cross-field compatibility, reference integrity, uniqueness, regex safety, and count limits remain semantic-validator responsibilities.

**Consequences**

- Every top-level, form, step, section, field, option, validation, condition, and settings object is closed with `additionalProperties: false`.
- Nullable presentation values and inactive validation values remain present explicitly.
- The twelve approved field types, condition operators, and actions are enumerated in the shared contract.
- Laravel and FastAPI must load the root contract artifact rather than maintaining independent allowed-value lists.
- Stable identifiers are bounded non-empty strings in v1; client generation details remain a Milestone 2 decision.

**Follow-up decisions**

- Semantic validation compatibility rules and schema limits.
- JSON column representation and maximum persisted size.
- Field-key and stable-ID generation behavior.

## Decision 038: Native JSON Storage with Bounded Schema Resources

**Date:** 2026-08-05

**Milestone:** 1 - Core Domain, Shared Schema, and Tenancy

**Status:** Approved; shared configuration implemented, with persistence and enforcement pending the form-version service.

**Approved option:** Option A - store normalized schemas in native MySQL JSON and enforce configurable aggregate limits before persistence in both Laravel and FastAPI.

**Context**

The strict contract bounds individual strings but does not bound total document size or aggregate collections. Unbounded client or AI output could consume excessive validation, rendering, storage, and network resources.

**Options considered**

- Option A: Native MySQL JSON with configurable application-level resource limits.
- Option B: LONGTEXT containing canonical JSON.
- Option C: Relational step, section, field, and option tables.

**Rationale**

Native JSON preserves the single-document source of truth and gives database-level JSON validity. Explicit application limits bound work before persistence and can be mirrored across both services.

**Approved defaults**

- Canonical JSON bytes: 1,048,576 (1 MiB).
- Steps: 20.
- Sections per step: 30.
- Fields per form: 150.
- Options per field: 100.
- Conditions per form: 300.

**Accepted trade-offs**

- Arbitrary field-level SQL querying is not optimized.
- Environment values must remain synchronized between Laravel and FastAPI deployments.
- Increasing limits can increase request, AI, validation, rendering, and storage costs.

**Consequences**

- `form_versions.schema_json` uses MySQL's native JSON type.
- Laravel and FastAPI expose the same named settings and defaults.
- The semantic validator rejects over-limit schemas before canonical persistence.
- Tests must cover every exact boundary and its first rejected value.

**Follow-up decisions**

- Semantic validation compatibility rules and error representation.
- Form/version migrations and persistence service.

## Why a Separate FastAPI Service

Pending Milestone 0 approval.

## Why a Shared JSON Schema Contract

Pending Milestone 1 approval.

## Why Immutable Form Versions

Pending Milestone 1 approval.

## Why MySQL JSON Plus Search Projection

Pending Milestone 1 approval.

## Why Redis and Queues

Pending Milestone 0 approval.

## Why Deterministic Parsing Comes Before AI

Pending Milestone 5 approval.

## Tenant Isolation Approach

Pending Milestone 1 approval.

## Field-Key and Stable-ID Strategy

Pending Milestone 1 approval.

## Public URL Strategy

Pending Milestone 3 approval.

## File Storage Strategy

Pending Milestone 3 approval.

## AI Provider Abstraction

Pending Milestone 4 approval.

## AI Repair and Retry Limits

Pending Milestone 4 approval.

## Part D: Multi-Tenant Isolation

Pending Milestone 6 approval.

## Part D: Versioning and Rollback

Pending Milestone 6 approval.

## Part D: Conditional Logic

Pending Milestone 6 approval.

## Trade-Offs Accepted

See the accepted trade-offs recorded with each decision.

## Known Limitations

The project is currently in Milestone 0 and has not yet been scaffolded.

## What I Would Build With Two More Weeks

To be completed during project finalization.
