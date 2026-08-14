## Context
JMonitor is a web monitoring application for PHP web stacks.
It collects metrics from servers (MySQL, Redis, Apache, Nginx, Caddy, PHP, Symfony, FrankenPHP) and provides dashboards, alerts, and notifications.
It's designed to simplify the visualization of server and stack metrics (PHP, MySQL, Redis, Nginx, etc.). It provides clear dashboards with gauges and charts, making metrics easy to understand for both developers and non-experts. Its goal is to make performance analysis and issue detection fast and accessible.

This project is the SaaS and main application. It contains the endpoint for collecting metrics, and consumes them to generate dashboards and alerts.

The project follows an **open-core** model: one public repo (AGPL-3.0) serving two editions — the paid **cloud** edition (this production deployment) and a free **self-hosted** edition distributed as a Docker image (`jmonitor/jmonitor` on Docker Hub, mirrored on ghcr.io). See "Editions" below.

Two other PHP packages are part of the project, included as composer dependencies (in the vendor folder):
- [collector](https://github.com/jmonitor/collector) contains collectors (written in PHP) which are used to collect metrics from servers.
- [jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) integration of jmonitor/collector library into Symfony projects. Also contains a Symfony collector.

They are also included here: JMonitor uses JMonitor to monitor itself. `make link` / `make unlink` swap them to local path repositories (`../collector`, `../jmonitor-bundle`) for cross-package development.

## Project Overview

- **Stack:** PHP 8.4, Symfony 8, Doctrine ORM, InfluxDB 2.x, MySQL, Redis, Mercure, Stripe (cloud only), Sentry (cloud only)
- **Hosting (cloud edition):** Clever Cloud with FrankenPHP (worker mode) + Caddy

## Editions

Behavior is gated by the `APP_EDITION` env var (`cloud` default / `selfhosted`) via the injectable `App\Plan\Edition` enum service:

- **Plan seam:** `App\Plan\PlanResolver` is the single entry point for resolving a project's plan (instead of `Project::getCurrentPlan()`). Self-hosted resolves to `Plan::SELF_HOSTED` (unlimited, fixed 1-year InfluxDB retention).
- **Cloud-only gating:** billing/webhook routes return 404, billing menus/screens hidden, Stripe commands neutralized in self-hosted. The **collector** rate limiter uses a `no_limit` policy (`collector.selfhosted` in `config/packages/rate_limiter.yaml`) — the registration and password-reset limiters are unchanged and apply in both editions. OAuth sign-in routes are cloud-only too — but only the *routes*: `GoogleAuthenticator` stays registered in `security.yaml` because programmatic login after registration and password reset goes through it.
- **Registration:** `App\Security\Registration\RegistrationGate` decides whether an account can be created without an invitation (cloud yes, self-hosted no), exposed to Twig as `registration_open()`. The invitation flow itself is identical in both editions: `GET /join/{uniquid}` is the single public entry point (routes to a confirmation page, the login form, or registration), `/register/{uniquid}` locks the address to the invited one and accepts the invitation on submit. `App\Project\InvitationAccepter` is the only path turning an invitation into a membership, shared with the in-app accept action.
- **Errors:** Sentry is force-disabled in self-hosted (`CloudOnlyEnvVarProcessor` empties `SENTRY_DSN`); prod errors go by email instead, driven by `ERROR_MAIL_TO` (NullHandler if empty).
- **Version:** `JMONITOR_APP_VERSION` is sealed into the self-hosted image at build time (`docker/selfhosted/Dockerfile`, fed by the publish workflow from the git tag) and read through the `app.version` parameter by `App\Version\AppVersion`; it defaults to `dev` everywhere else, including cloud. Not to be confused with `JMONITOR_VERSION`, which only selects the image tag to pull in `docker/selfhosted/compose.yaml`. The dashboard card (`Dashboard:Version`, self-hosted only) renders the version with no network access and pulls the up-to-date badge through a separate turbo-frame request (`/_version-update`). The versions an **agent** advertises are a separate axis, project-scoped and present in both editions: `App\Version\Package` lists them (the collector, and the Symfony bundle when there is one), `AdvertisedVersion` reads what each push sent and the `Dashboard:Status` card badges each against its own repository (`/p/{uuid}/_version-update/{package}`).
- **Packaging:** `docker/selfhosted/` (multi-stage Dockerfile, compose stack, entrypoint, own php.ini + README). `app:install` (idempotent, `src/Install/`: `EnvChecker`, `AdminProvisioner`, `SelfMonitoringProvisioner`) bootstraps a fresh install. Image published on Docker Hub (+ ghcr.io mirror) by `.github/workflows/docker-publish.yml` on `v*` tags (multi-arch amd64/arm64), with a compose smoke test.

## Architecture

Multi-host routing via `config/routes.yaml`:
- `collector.{domain}` → metrics collection API (API key auth)
- `dash.{domain}` → user dashboard (ROLE_USER)
- `admin.{domain}` → EasyAdmin panel (ROLE_ADMIN)

The collector routes are also registered on an internal Host
(`app.collector_internal_host`, env `JMONITOR_COLLECTOR_INTERNAL_HOST`, defaults to the
public one) for agents inside the app's own network — the self-hosted collector container
sets it to the compose service name, because libcurl resolves `*.localhost` to loopback
(RFC 6761) and could never reach `collector.<domain>` through Docker DNS.

Multi-tenant: User → ProjectUser (OWNER/ADMIN/VIEWER) → Project → InfluxDB bucket

## Key Directories

```
src/
  Admin/           EasyAdmin CRUD
  Alerting/        Alert checking strategies + notifications
  Bridge/          External integrations (InfluxDB, Stripe, Mercure, EOL API)
  Chart/           Chart config and unit conversion
  Collector/       Rate limiting for metric pushes
  Command/         Console commands (Demo/ = demo account, Dev/ = dev provisioning, InstallCommand)
  Controller/      HTTP endpoints (Admin/, Collector/, Dash/)
  Demo/            Synthetic metric generators for the public demo account
  Dev/             Self-monitoring collectors (JMonitor monitoring itself)
  Entity/          Doctrine entities + Enums/
  Install/         Self-hosted install: env checks + admin/self-monitoring provisioning
  Metrics/         Consumer parsers, InfluxDB queries, rendering
  MessageHandler/  Async handlers (MetricsReceived, CheckAlert)
  Notifier/        Notification channels
  Plan/            Edition + PlanResolver (cloud/self-hosted seam), Stripe sessions
  Security/        Voters, OAuth2 authenticators
  Twig/            Custom filters/functions
  Version/         App version + the packages an agent advertises, checked against the latest GitHub release
```

## Development Environment (Docker)

Local dev runs in Docker Compose (`compose.yaml`, project name `jmonitor-dev` — the self-hosted stack keeps `jmonitor`, so both can run on the same machine): FrankenPHP app container + MySQL + Redis + InfluxDB, driven by the `Makefile`. PHP/composer commands run **inside the app container** (`make sh` or `docker compose exec app ...`), not on the host.

```
make setup       # cold start: containers + composer install + migrations + dev provisioning
make up / down   # start / stop containers
make sh          # shell in the app container
make monitor     # real self-monitoring metrics into the dev project (foreground, jmonitor:collect -vv)
make demo        # synthetic-metrics worker feeding the demo account (compose profile "demo")
make provision   # re-run app:dev:provision (idempotent; links dev user to demo project once it exists)
make perms       # fix root-owned files created by containers (server runs as root on the bind mount)
make ca          # export Caddy's internal root CA (HTTPS mode) + print how to trust it
```

- URLs: http://dash.jmonitor.localhost, admin., collector. (`.localhost` resolves natively), InfluxDB UI on :8086 (`admin` / `jmonitor-dev`).
- Dev login: `dev@jmonitor.io` / `dev` (provisioned by `app:dev:provision`, own self-monitoring project, API key preset in `.env.dev`).
- Committed dev defaults in `.env.dev`; personal overrides in `.env.dev.local`. Messenger is synchronous in dev — no worker needed.
- Host port collisions: remap in gitignored `compose.override.yaml` (`ports: !override`).
- HTTPS (opt-in): `JMONITOR_HTTPS=1 make up` enables the `compose.https.yaml` overlay (Caddy internal CA, dashboard on `https://dash.jmonitor.localhost:8443`); `make ca` trusts the cert. See CONTRIBUTING.md "HTTPS in dev".

## Code Quality Commands

Run inside the app container (or via the `make test` / `make phpstan` / `make cs` shortcuts):

```
composer run phpunit                         # Run all tests
./vendor/bin/phpunit tests/Path/To/Test.php                   # Run single test file
./vendor/bin/phpunit tests/Path/To/Test.php::testMethodName   # Run single test method
composer run phpstan      # Static analysis (level 5)
composer run lint:check   # PHP-CS-Fixer check (@PER-CS style)
composer run lint:fix     # PHP-CS-Fixer fix
composer run rector:check # Rector check (PHP 8.4 migrations)
composer run rector:fix   # Rector apply fixes
./vendor/bin/igor-php .   # FrankenPHP worker-safety linter (config igor.json, baseline igor-baseline.json)
```

`igor-php` audits shared services for state leaking between requests under FrankenPHP worker mode (mutated properties without reset, superglobals, `exit()`...). New findings vs the baseline should be fixed, not baselined.

## Demo Account

A public, read-only demo account (`demo@jmonitor.io` / `demo`, project `"Demo project"`) fed by **synthetic collectors** (`src/Demo/`) pushing through the normal ingestion pipeline. `Project::isDemo()` suppresses side effects on fake data (no alert checking). Commands: `app:demo:provision` (idempotent) + `app:demo:run` (worker loop); `make demo` in dev.

**Before touching demo code, read [docs/demo.md](../docs/demo.md)** — identity resolution, generation pipeline (`DemoState` primitives), and metric-shape constraints.

## Coding Conventions

- `declare(strict_types=1)` in every file
- Full type declarations on all parameters and return types
- Readonly classes for immutable services
- Constructor-based DI with `#[Autowire]` attributes
- PHP 8 attributes for routes, entities, handlers (no YAML/XML annotations)
- Strategy pattern via autowired iterators (AlertCheckerInterface, ConsumerInterface)
- Enums for fixed value sets (Plan, Component, AlertMetric, ProjectRole, UserStatus)
- **Worker-safe services:** the app runs under FrankenPHP worker mode — shared services must not accumulate per-request state (reset mutated properties or make them readonly); `igor-php` enforces this
- Comments and docs: short and factual (1-3 sentences), no hosting-provider details in public-facing code/docs
- Never use Yoda conditions (`$var === 'x'`, not `'x' === $var`)
- **Keep docs in sync with code:** whenever a change modifies, contradicts, or leaves incomplete anything documented in this `CLAUDE.md`, the root `README.md`, `docker/selfhosted/README.md`, or `CONTRIBUTING.md`, update that doc in the same change — stale or contradictory docs are treated as part of the diff, not a follow-up
- **Changelog:** any user-visible change (feature, behavior change, fix, upgrade step) gets a line under `## [Unreleased]` in `CHANGELOG.md`, suffixed with `(cloud)` / `(self-hosted)` when edition-specific. Dependency bumps, refactors and CI changes are excluded. **Cutting a release is a maintainer task with its own procedure — read [docs/releasing.md](../docs/releasing.md) before tagging anything.**

## Frontend Stack

**Asset system:** Symfony AssetMapper with native ESM — no Webpack/Vite build step. Packages are declared in `importmap.php` (CDN-based) and installed into `assets/vendor/`. Entry point is `assets/app.js`, loaded in templates via `{{ importmap('app') }}`.

**CSS:**
- Bootstrap 5.3.5, dark theme only (`<html data-bs-theme="dark">`)
- Custom CSS in `assets/css/app.css` (imports modular partials: `_card.css`, `_sidebar.css`, etc.)
- CSS variables override Bootstrap defaults
- **Fonts are served locally, never from a CDN** — woff2 files in `assets/fonts/` (+ their OFL
  licences), `@font-face` in `assets/css/_fonts.css`. Self-hosted instances on a private network
  must not depend on Google Fonts. `_fonts.css` documents how to refresh the files.

**JavaScript:**
- **Stimulus 3.2.2**
- **Turbo 8** 
- **Chart.js 4.4.8** + annotation plugin + moment adapter — for all metric charts
- **date-fns 4** — date formatting in Stimulus controllers
- **toastify-js** — toast notifications
