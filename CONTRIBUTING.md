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

The dev stack runs under the Compose project `jmonitor-dev`, the self-hosted stack
(`docker/selfhosted/`) under `jmonitor`. Distinct names are what let you run both at once
on the same machine — otherwise Compose treats them as one project and each `up` replaces
the other's containers while inheriting its volumes.

If your dev stack predates that split it still runs as `jmonitor`: stop it once with
`docker compose -p jmonitor down`, then `make setup`. Volumes are prefixed by the project
name, so the new stack starts on empty ones — a fresh database, and a new Caddy CA to
trust in HTTPS mode. To keep the old data instead, copy each volume over before starting:

```bash
for v in mysql-data influxdb-data caddy-data caddy-config; do
  docker volume create "jmonitor-dev_$v" >/dev/null
  docker run --rm -v "jmonitor_$v:/from" -v "jmonitor-dev_$v:/to" alpine \
    sh -c 'cp -a /from/. /to/'
done
```

The leftover `jmonitor_*` volumes are then orphans — `docker volume rm` them when you are
sure you no longer need them.

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
- Add a line under `## [Unreleased]` in `CHANGELOG.md` for anything a user would
  notice: new features, behavior changes, fixes, upgrade steps. Dependency
  bumps, refactors and CI changes do not belong there. Suffix the line with
  `(cloud)` or `(self-hosted)` when it only applies to one edition.

## Releasing

Releases are self-hosted image versions, published by
`.github/workflows/docker-publish.yml` on `v*` tags.

1. In `CHANGELOG.md`, rename `## [Unreleased]` to `## [X.Y.Z] - YYYY-MM-DD`
   using the date you are tagging, and add a fresh empty `## [Unreleased]`
   above it.
2. Update the link definitions at the bottom: point `[Unreleased]` at
   `compare/vX.Y.Z...HEAD` and add a `[X.Y.Z]` line for the new tag.
3. Commit, then `git tag vX.Y.Z && git push origin master --tags`.
4. Watch the run: it re-checks that the changelog has an entry for the tag,
   builds and pushes the multi-arch image, then opens the GitHub release with
   that changelog section as its body.

Put anything a self-hoster must do by hand (new env var, long migration, changed
default) under an `### Upgrade notes` heading at the top of the version's entry,
before the other sections.

Use plain `vX.Y.Z` tags. A prerelease tag (`v1.0.0-rc.1`) produces no `latest`
image tag, which breaks the `${JMONITOR_VERSION:-latest}` default of the
self-hosted Compose stack.
