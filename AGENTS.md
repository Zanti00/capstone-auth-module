# AGENTS.md

Capstone auth module: Laravel 13 `auth-service` (REST API, OAuth2/Passport) + Vue 3 `web-interface` (admin SPA), orchestrated with Docker Compose behind a shared Nginx gateway.

---

## 🤖 AI Agent Context & Ecosystem Overview

This repository is the central authentication & identity authority (**Auth Service**) for an enterprise ecosystem. There are **4 connected downstream subsystems** that integrate with and consume this authentication service:

| Subsystem Acronym | Full System Name                              | Description & Interaction Context                                                                 |
| :---------------- | :-------------------------------------------- | :------------------------------------------------------------------------------------------------ |
| **CRMS**          | Contract Management System                    | Consumes Auth API & OAuth2 tokens for contract management user roles and authorization            |
| **SERMS**         | Smart Expense Reimbursement Management System | Consumes Auth API & OAuth2 SSO for expense reimbursement claims and permission verification       |
| **PRS**           | Productivity Report System                    | Consumes Auth API for user session validation, reporting permissions, and activity metrics access |
| **TS**            | Ticketing System                              | Consumes Auth API & OAuth2 tokens for helpdesk/support ticketing workflow permissions             |

### ⚠️ Directives for AI Agents

When working on this repository, AI agents MUST take note that changes to authentication endpoints, Passport/OAuth2 client configurations, token payloads, RBAC roles/permissions, internal service secrets (`INTERNAL_SERVICE_SECRET`), encryption keys (`INTERNAL_ENCRYPTION_KEY`), or shared gateway routes directly affect all 4 connected subsystems (**CRMS**, **SERMS**, **PRS**, **TS**).

- **API Contracts & Compatibility**: Preserve API contract backward compatibility when refactoring or modifying auth/token endpoints.
- **Cross-Subsystem Integration**: Ensure gateway, CORS, and network settings facilitate communication over the shared Docker network (`shared-capstone-network`).

---

## Setup (host, one-time)

Prerequisites: Docker Desktop v4.x+ (WSL2 backend recommended on Windows), Git, Node.js v20+.

1. Create the shared network once: `docker network create shared-capstone-network` (created on the host, not in compose).
2. Copy `.env.example` → `.env` in **both** the repo root and `auth-service/`. The root `.env` feeds Compose and wins for shared values (e.g. `REDIS_HOST=auth-redis`); the service `.env.example` is only a fallback for non-Compose runs.
3. `docker compose up -d --build`
4. `docker exec -it auth-service php artisan key:generate`
5. `docker exec -it auth-service php artisan migrate --seed`

Endpoints: Web Interface via gateway `http://localhost:5173`, Auth Service direct `http://localhost:8000`, health check `http://localhost:8000/up`.

Windows note: after code edits, entrypoint fixes CRLF in Passport keys automatically (`sed -i 's/\r$//'` on `storage/oauth-*.key`); a mounted file with CRLF endings is the usual cause of "key file not readable" errors.

## Do not fight the entrypoint

`auth-service/docker-entrypoint.sh` runs on every `docker compose up`. Do not duplicate or bypass its work:

- Waits for DB (`nc -z "$DB_HOST" "$DB_PORT"`), runs `composer install`.
- Creates `.env` from `.env.example` if missing; runs `key:generate --force` only when `APP_KEY` is absent.
- Auto-generates `INTERNAL_ENCRYPTION_KEY` (`openssl rand -hex 16`) when empty or still `your_secure_32_char_key_here`.
- `migrate --force`; seeds only when `User::count() == 0` (idempotent — do not re-seed manually).
- Generates Passport keys (`passport:keys --force`) and a personal access client (`passport:client --personal`) only when missing.
- `cache:clear` + `optimize:clear`, then `exec "$@"`.

To skip migrations/seed on a service start: `SKIP_MIGRATIONS=true docker compose up -d auth-service`.

## Environment wiring (Compose)

- `docker-compose.yml` passes root-`.env` values via `${VAR}` interpolation into the container (`DB_HOST`, `REDIS_HOST`, `APP_ENV`, `APP_DEBUG`, all Brevo/MAIL\_\* vars, `INTERNAL_SERVICE_SECRET`, `VENDOR_MANAGEMENT_URL`, `INTERNAL_ENCRYPTION_KEY`).
- Hardcoded in compose (ignore `.env` for these): `DB_PORT=3306`, `PHP_CLI_SERVER_WORKERS=4`.
- `depends_on`: `db` (condition: `service_healthy`) and `auth-redis`.
- When adding a new env var, wire it in `docker-compose.yml` env block, `auth-service/.env.example`, **and** root `.env.example`, or it will silently be `null` in the container.

## Config / runs

- `composer run setup` = install → create `.env` → `key:generate` → `migrate --force` → `npm install --ignore-scripts` → `npm run build` (local, non-Compose workflow).
- `composer run dev` = concurrently runs `php artisan serve`, `queue:listen --tries=1 --timeout=0`, `pail`, `npm run dev`.
- Management: `docker compose ps`, `docker compose logs -f [service]`, `docker compose down` (preserves DB/redis volumes).
- Common failure: Docker daemon pipe error `open //./pipe/dockerDesktopLinuxEngine` → start/restart Docker Desktop.

## Stack specifics

- `auth-service`: PHP 8.3, Laravel 13, Passport OAuth, Predis (redis), `php artisan serve` on `0.0.0.0:8000` inside a `php:8.3-fpm-alpine` container; `INTERNAL_ENCRYPTION_KEY` must be a unique 32-char string.
- `web-interface`: Vue 3 SPA; build = `vue-tsc -b && vite build`; uses axios, vue-router v4, crypto-js + jsencrypt (client-side crypto), shadcn-vue/radix-vue, **Tailwind 3.4 (pinned — do not bump to v4)**.
- `auth-service` Vite is Tailwind 4 with `package.json` `"type": "module"` — do not copy Tailwind v4 syntax across the two packages.

## Architecture / code style

- Layered: Controllers → Services → Repositories; constructor injection for loose coupling (e.g. `UserService` injects `UserRepository`).
- PSR-12 (backend); `camelCase` variables/methods, `PascalCase` classes (PHP and TS/Vue).
- Eager load (`with()`) to avoid N+1; Redis caching; queue offloading; Form Request validation; Eloquent Resources for API responses.
- Vue components live under `web-interface/src/components` (folder-based); feature-based directory layout.
- DocBlocks explain rationale, not implementation.

## Contribution workflow (repo-enforced in Contributing.md)

- Every issue title starts with `[Bug]`, `[Feature]`, or `[Task]`; template fields: Description, Steps to Reproduce, Expected vs Actual, Environment, Supporting Evidence. Open/check for an issue **before** starting work.
- Commits: Conventional Commits, e.g. `feat: implement authentication service`.
- Sync: `git checkout main && git pull origin main && git checkout feat/your-feature-name && git merge main`.
- PR: title `[#<Issue_Number>] <Short, imperative-mood description>` (e.g. `[#105] Add session timeout logic to admin layout`); body = `### Summary`, `### Linked Issue` (`Closes #<Issue_Number>`), `### Changes`, `### Testing`; target `main`.
