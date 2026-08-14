# Demo Account

A public, read-only demo account fed by **synthetic (fake) collectors**. It lets visitors explore real dashboards/alerts without owning servers. JMonitor pushes self-generated metrics through the *normal* ingestion pipeline — no HTTP, no API key, no rate limiter, no backfill (metrics build forward from worker start).

## Credentials & identity

Hardcoded as constants `Project::DEMO_EMAIL` / `Project::DEMO_NAME`, no env vars; `DemoProvisionCommand::EMAIL` / `PROJECT_NAME` alias them:

- User `demo@jmonitor.io` / `demo`, status `ACTIVE`
- Project `"Demo project"`, all `Component`s enabled, role `VIEWER` (read-only)
- Active **PRO** `Subscription` (+10 years, no auto-renew) — required so `Consumer::consume()` allows InfluxDB writes
- The demo project is resolved **by identity** (email + project name) via `ProjectRepository::findDemoProject()`, not by an ID env var. `Project::isDemo()` performs the same two-part check (name + a linked user with the demo email) and is used to suppress side effects on fake data (e.g. `AlertCheckerListener` skips alert checking entirely for the demo project, so synthetic metrics never pause an alert or send a notification).

## Commands

- `app:demo:provision` — idempotent (by email); creates/updates the user, project, PRO subscription, and VIEWER link. Run on deploy in production (non-blocking on failure).
- `app:demo:run` — long-running worker loop. Each tick: `DemoBatchBuilder::build()` → `dispatch(MetricsReceivedMessage)` → `DemoState::persist()` → sleep `--interval` (default 15s). Options: `--interval`, `--time-limit` (clean SUCCESS exit; restart is the orchestrator's job). Implements `SignalableCommandInterface` (SIGINT/SIGTERM/SIGQUIT) and isolates per-tick errors so one failure doesn't crash the loop.

In dev, `make demo` runs both commands in a dedicated container (compose profile `demo`).

## Generation pipeline (`src/Demo/`)

- `Generator\DemoMetricGeneratorInterface` — tagged `app.demo_generator` (autoconfigured); each generator maps to one `Consumer` and returns a metrics array. One generator per component (System, Apache, Nginx, Caddy, FrankenPHP, PHP, Redis, Symfony, MySQL\*, Postgres\*).
- `DemoBatchBuilder` — iterates the tagged generators, **skips components not enabled** on the project, assembles the batch (name, version, metrics, threw, duration).
- `State\DemoState` — cache-backed (Redis in prod, key `demo.state`, 30-day TTL); keeps values coherent across worker restarts. Three primitives: `counter()` (monotonic cumulative counters), `walk()` (bounded mean-reverting random walk for instantaneous gauges), `seasonality()` (shared daily traffic curve, ~0.30 at 04:00 / ~1.00 at 16:00, so components "breathe" together).

## Notes

- Generators emit metrics in the exact shape each `Consumer` expects — validate against `Consumer::getConstraints()` in tests (see `tests/Demo/`), and cross-check the rendered shape since constraints often validate sub-arrays loosely.
- `SymfonyGenerator` renders scheduler tasks with `AsPeriodicTask` notation (`every <interval> with <jitter> second jitter`), which the dashboard's `SchedulerTaskBag` splits into `trigger` + `jitter`.
- The demo agent advertises the `jmonitor/collector` and `jmonitor/jmonitor-bundle` versions installed in this app (`DemoAgentVersions`), so the collector status card checks them against real releases like it does for any project.
- Demo code (`App\Demo`, `App\Command\Demo`) is distinct from `src/Dev/` self-monitoring.
