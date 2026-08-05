# Contributing to JMonitor

Thanks for your interest in contributing!

## Before you start

- **Bugs**: open an issue with steps to reproduce, expected vs. actual behavior, and your environment.
- **Features**: open an issue first to discuss the idea before investing time in a pull request.
- **Security issues**: never open a public issue — see [SECURITY.md](SECURITY.md).

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

Want real metrics from your dev stack? `make monitor` runs the collector loop in the
foreground, pushing the dev stack's own metrics (FrankenPHP, MySQL, Redis, ...) into a
dedicated dev project — log in with `dev@jmonitor.io` / `dev` (provisioned by
`make setup`, API key preset in `.env.dev`). Once the demo project exists (`make demo`),
run `make provision` to also link this login to it, so you can browse synthetic data for
every component.

Common commands: `make sh` (shell in the app container), `make monitor`, `make test`,
`make phpstan`, `make cs`, `make down`. If a host port collides (80, 3306, 6379, 8086),
override it in a gitignored `compose.override.yaml`.

### HTTPS in dev

The stack is HTTP by default. To run it over HTTPS instead — closer to production
(secure cookies) — enable the `compose.https.yaml` overlay:
Caddy issues the certificates itself from its internal CA, no external tool needed.

```bash
export JMONITOR_HTTPS=1   # add it to your shell profile to make it permanent
make up
make ca                   # exports the root CA + prints how to trust it
```

`make ca` writes `caddy-root-ca.crt` (gitignored) and prints the one-off command to
add it to your certificate store; do it once and restart the browser. The CA lives
in a named volume, so it survives `make down` — only `docker compose down -v` throws
it away, and you would then have to trust the new one.

The overlay serves HTTPS on `8443` (mirroring the `8081` HTTP convention) and keeps
`80` for the HTTP→HTTPS redirects. To use a different HTTPS port, export
`JMONITOR_DEV_HTTPS_PORT` — one variable moves both the published port and the
HTTP→HTTPS redirect target.
Remap either port in your `compose.override.yaml` as usual. Server-side traffic
(Mercure publishing, self-monitoring pushes, php-metrics) moves to a plain-HTTP `:2021`
site inside the container, never published.

Prefer running without Docker? See `compose.yaml` for the required services and
`.env.dev` for the expected DSNs, and point `APP_DOMAIN` subdomains at your own server.

## Tests & quality checks

Every pull request must pass CI, which runs the equivalent of:

```bash
make cs       # PHP-CS-Fixer (@PER-CS style)
make phpstan  # static analysis
make test     # PHPUnit test suite
# or, inside the container (make sh):
composer run lint:check / phpstan / phpunit / rector:check
php bin/console lint:twig templates
php bin/console lint:yaml config
```

Use `composer run lint:fix` (inside the container, via `make sh`) to fix style issues
automatically. A pre-commit hook running these checks is installed automatically by
`composer install`, but only runs if PHP is available on the host — with a Docker-only setup it silently no-ops, and CI catches style and analysis issues instead.

## Coding conventions

- `declare(strict_types=1)` in every PHP file, full type declarations everywhere
- Constructor-based dependency injection; `readonly` classes for immutable services
- PHP attributes for routes, entities and handlers (no YAML/XML annotations)
- Enums for fixed value sets; strategy pattern via autowired iterators
- Code and comments in English

## Pull requests

- Keep PRs focused: one feature or fix per PR.
- Add or update tests for any behavior change.
- Reference the related issue in the PR description.
