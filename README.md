# JMonitor

[![CI](https://github.com/jmonitor/jmonitor/actions/workflows/ci.yml/badge.svg)](https://github.com/jmonitor/jmonitor/actions/workflows/ci.yml)
[![License: AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-blue.svg)](LICENSE)

**Monitoring for PHP web stacks.** JMonitor collects metrics from your servers (MySQL, Redis, Apache, Nginx, Caddy, PHP, Symfony, FrankenPHP…) and turns them into clear dashboards, alerts and notifications — gauges and charts that make sense to developers and non-experts alike.

🌐 Website: [jmonitor.io](https://jmonitor.io) — try the [live demo](https://dash.jmonitor.io) (`demo@jmonitor.io` / `demo`).

## Features

- **Dashboards** — one page per component, with gauges, charts and version badges. Real-time updates over Mercure.
- **Alerts** — threshold alerts on key metrics, end-of-life / outdated version checks, "no data received" detection.
- **Notifications** — email and chat channels via Symfony Notifier.
- **Multi-project, multi-user** — projects with owner/admin/viewer roles.
- **Lightweight agent** — a small PHP collector pushes metrics over HTTPS; nothing to install besides Composer packages.

## How it works

1. The [jmonitor/collector](https://github.com/jmonitor/collector) package (or [jmonitor/jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) for Symfony apps) runs on your server and periodically pushes metrics to the collection API.
2. This application ingests the metrics (Symfony Messenger), stores time series in InfluxDB and relational data in MySQL.
3. Dashboards render the series, and alert checkers evaluate incoming data to trigger notifications.

This repository is the main web application (SaaS at [jmonitor.io](https://jmonitor.io)). It monitors itself with its own collector.

## Self-hosting

JMonitor is free to self-host: the full stack (app, worker, MySQL, Redis,
InfluxDB) runs from a prebuilt Docker image published on GitHub Container
Registry. See the **[self-hosting guide](docker/selfhosted/README.md)**.

## Stack

- PHP 8.4, Symfony 8, Doctrine ORM
- MySQL (application data), InfluxDB 2.x (time series), Redis (cache, sessions, rate limiting)
- Mercure (real-time dashboard updates)
- Bootstrap 5 + Stimulus + Turbo + Chart.js, served with Symfony AssetMapper (no Node.js build)
- Stripe (billing, cloud edition only)

## Development setup

Requirements: [Docker](https://docs.docker.com/get-docker/) (Compose v2) and `make`.
On Windows, use Docker Desktop with the WSL2 backend and clone the repository **inside the
WSL2 filesystem** (e.g. `~/jmonitor`, not `C:\...`) — bind-mount I/O on NTFS is very slow.

```bash
git clone git@github.com:jmonitor/jmonitor.git
cd jmonitor
make setup
```

That's it. The stack (FrankenPHP + Caddy + Mercure, MySQL, Redis, InfluxDB) is up:

- http://dash.jmonitor.localhost — user dashboard
- http://admin.jmonitor.localhost — admin panel
- http://collector.jmonitor.localhost — metrics collection API
- http://localhost:8086 — InfluxDB UI (`admin` / `jmonitor-dev`)

Subdomains of `.localhost` resolve to your machine natively — no hosts file, no TLS setup.
Dev defaults (committed, throwaway) live in `.env.dev`; personal overrides go to
`.env.dev.local`. In the `dev` environment messages are handled synchronously — no
Messenger worker is needed.

Want populated dashboards without owning a server? `make demo` starts a worker feeding
synthetic metrics to the public demo account — log in with `demo@jmonitor.io` / `demo`.

Want the app to monitor itself with real metrics? `make monitor` runs the collector loop
in the foreground, pushing this dev stack's own metrics (FrankenPHP, MySQL, Redis, ...)
into a dedicated dev project — log in with `dev@jmonitor.io` / `dev` (provisioned by
`make setup`, API key preset in `.env.dev`). Once the demo project exists (`make demo`),
run `make provision` to also link this login to it, so you can browse synthetic data for
every component.

Common commands: `make sh` (shell in the app container), `make monitor`, `make test`,
`make phpstan`, `make cs`, `make down`. If a host port collides (80, 3306, 6379, 8086),
override it in a gitignored `compose.override.yaml`.

Prefer running without Docker? See `compose.yaml` for the required services and
`.env.dev` for the expected DSNs, and point `APP_DOMAIN` subdomains at your own server.

## Tests & code quality

```bash
make test          # PHPUnit test suite
make phpstan       # static analysis
make cs            # PHP-CS-Fixer (@PER-CS)
# or, inside the container (make sh):
composer run phpunit / phpstan / lint:check / rector:check
php bin/console lint:twig templates
php bin/console lint:yaml config
```

See [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines and [SECURITY.md](SECURITY.md) for how to report vulnerabilities.

## Related packages

- [jmonitor/collector](https://github.com/jmonitor/collector) — the PHP collectors that gather metrics on your servers
- [jmonitor/jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) — Symfony integration of the collector (plus a Symfony-specific collector)

## License

JMonitor is open source under the [AGPL-3.0](LICENSE) license.
