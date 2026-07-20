# Self-hosting JMonitor

Run your own JMonitor instance with Docker Compose, using the prebuilt image
from GitHub Container Registry (`ghcr.io/jmonitor/jmonitor`). The stack:

| Service | Role |
|---|---|
| **app** | Web application (FrankenPHP worker mode + Caddy, Mercure hub embedded) |
| **worker** | Messenger consumer: metrics ingestion, alerts, emails, scheduled tasks |
| **collector** | Self-monitoring agent (`jmonitor:collect`, see Self-monitoring below) |
| **mysql** | Application data |
| **influxdb** | Metric time series (InfluxDB OSS 2.x) |
| **redis** | Cache, sessions, message queue |

## Requirements

- Docker with Compose v2, on any Linux host (2 GB RAM is a comfortable start).
- A (sub)domain you control, with **three DNS records** (see below) — not
  needed for a local test (`.localhost` works without any DNS).
- Something terminating TLS in front of the stack: a reverse proxy you run
  (Caddy, nginx, Traefik…) or Cloudflare's proxy (see below) — the stack
  itself speaks plain HTTP on a local port, which is also fine on its own for
  local tests and private networks.

## 1. DNS

JMonitor serves three hosts under one root domain (`APP_DOMAIN`), e.g. with
`APP_DOMAIN=monitoring.example.com`:

| Host | Purpose |
|---|---|
| `dash.monitoring.example.com` | Dashboard (users) |
| `collector.monitoring.example.com` | Metrics collection API (agents push here) |
| `admin.monitoring.example.com` | Admin panel |

**Production** — create three `A`/`AAAA`/`CNAME` records pointing at your
server, or one wildcard record (`*.monitoring.example.com`) which covers them
all.

**Trying it locally** — no DNS needed: pick a domain under `.localhost`
(e.g. `APP_DOMAIN=jmonitor.localhost`). Browsers resolve any subdomain of
`.localhost` to your own machine, so after `docker compose up -d` the
dashboard is at `http://dash.jmonitor.localhost:8080` — you can also skip
the reverse proxy step entirely.

> **Warning:** without a TLS proxy, the real-time dashboard updates are
> degraded: the stack assumes `https://` for Mercure (server-sent events), so
> the browser cannot subscribe and dashboards only refresh on page reload.
> Everything else works normally. For full parity, put a local TLS proxy in
> front (e.g. Caddy with `tls internal`) and follow the production steps.

## 2. Configure

```bash
mkdir jmonitor && cd jmonitor
curl -fsSLO https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/compose.yaml
curl -fsSL -o .env https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/.env.example
```

Edit `.env`: set `APP_DOMAIN`, replace every `CHANGE_ME` (generate secrets with
`openssl rand -hex 32`) and set the initial admin credentials.

For alert and error emails, also set your SMTP DSN (see
[Email & error reporting](#email--error-reporting)).

Set `TZ` to your timezone now rather than later (see [Timezone](#timezone)).

The app refuses to start while a `CHANGE_ME` placeholder remains.

## 3. Reverse proxy

> **Note:** this step is optional, but recommended — it is what serves
> JMonitor over HTTPS. Skipping it is fine for local tests and private
> networks (see "No TLS at all?" at the end of this section).

Forward the three hosts to the stack's HTTP port (default `8080`), preserving
the `Host` header. TLS certificates are handled by your proxy (three individual
certificates via HTTP challenge work fine — no wildcard certificate needed).

**Caddy** (`/etc/caddy/Caddyfile`) — certificates are automatic:

```
dash.monitoring.example.com,
collector.monitoring.example.com,
admin.monitoring.example.com {
    reverse_proxy 127.0.0.1:8080
}
```

**nginx** (with certificates from certbot):

```nginx
server {
    listen 443 ssl http2;
    server_name dash.monitoring.example.com collector.monitoring.example.com admin.monitoring.example.com;

    # ssl_certificate ...; ssl_certificate_key ...;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host $host;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        # Mercure (real-time dashboard updates) uses Server-Sent Events:
        # disable buffering and allow long-lived connections.
        proxy_buffering off;
        proxy_read_timeout 24h;
        proxy_http_version 1.1;
    }
}
```

If your proxy runs on **another machine**, add its IP to `TRUSTED_PROXIES`
(comma-separated) in `.env` — the default already trusts private network
ranges, which covers a proxy on the same host (Docker bridge) out of the box.
You'll also need to set `JMONITOR_HTTP_BIND=0.0.0.0` in `.env` (the port binds
to `127.0.0.1` only by default) — and firewall the port, since it's now
reachable from outside the host.

**Cloudflare** — if your DNS is on Cloudflare, you can skip running a proxy
altogether: enable the proxy ("orange cloud") on the three records and
Cloudflare terminates TLS at its edge. Set the SSL/TLS mode to *Flexible* (or
*Full* with a Cloudflare origin certificate, to also encrypt the
Cloudflare→server leg), expose the stack on port 80 (`JMONITOR_HTTP_PORT=80`,
`JMONITOR_HTTP_BIND=0.0.0.0` in `.env`), and ideally firewall the port to
[Cloudflare's IP ranges](https://www.cloudflare.com/ips/).

**No TLS at all?** The stack also works over plain HTTP — nothing breaks
except the real-time dashboard updates, which assume `https://` (same
degradation as the local-test case, see the warning in the DNS section).
Credentials then travel unencrypted, so keep that for local tests and private
networks, not for anything reachable from the internet.

## 4. Start

```bash
docker compose up -d
```

The first start takes a minute: the app container validates the configuration,
waits for MySQL/InfluxDB, runs the database migrations and creates your admin
account (`app:install` — it logs what it does, `docker compose logs -f app`).
Every later start re-runs the same sequence, which is idempotent: upgrades
apply migrations automatically, and the admin is never recreated.

Then log in at `https://dash.<APP_DOMAIN>` with `ADMIN_EMAIL` / `ADMIN_PASSWORD`.

## Self-monitoring

Your instance monitors itself out of the box: the first start provisions a
**jmonitor** project (owned by the initial admin) and the `collector` compose
service feeds it with the stack's own metrics (system, PHP, Caddy/FrankenPHP,
MySQL, Redis, Symfony). Log in and you already have data.

Note: system CPU/memory/load are the host machine's real values. Disk usage
only covers the partition backing Docker's storage (`/var/lib/docker`).

Don't want it? Set `SELF_MONITORING=0` in `.env` **before the first start**:
no project is created and the collector service stays idle. After the fact:
delete the project from the dashboard and stop the service
(`docker compose stop collector`) — a deleted project never comes back.
Re-enabling later is manual: the project is only auto-created on the very
first start — create a project in the dashboard, put its API key in `.env`
as `JMONITOR_API_KEY` (and remove `SELF_MONITORING=0` if set), then recreate
the service so it picks up the new `.env`: `docker compose up -d collector`.

## 5. Send metrics

To monitor **your own servers and apps**, create a project in the dashboard,
grab its API key, and install a collector in the PHP project(s) you want to
monitor — it's a Composer package that reports the whole stack around the app
(PHP, MySQL, Redis, web server…):

- [jmonitor/collector](https://github.com/jmonitor/collector) — for any PHP project
- [jmonitor/jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) — Symfony integration

Point the collector at `https://collector.<APP_DOMAIN>`.

## Email & error reporting

The mailer carries both **alert notifications** and **production error
reports**. To enable them, configure an SMTP transport:

- `MAILER_DSN=smtp://user:password@host:587` — outgoing mail transport.
- `MAILER_SENDER_EMAIL` — sender address.
- `ERROR_MAIL_TO` — where production errors are emailed (deduplicated). Leave
  empty to disable.

Without SMTP the app degrades gracefully: alert emails are silently dropped
and errors only land in the container logs (`docker compose logs app`).

## Timezone

Charts and relative times ("3 minutes ago") always render in each visitor's
**browser** timezone — nothing to configure there. `TZ` (IANA identifier, e.g.
`Europe/Paris`) covers everything rendered server-side so it matches: absolute
dates in the dashboard and admin, alert/error emails, log timestamps, MySQL.

**Set it once at install and keep it.** Dates are stored in the database in
that timezone, so changing `TZ` on a running instance shifts how every
already-stored date is read (metric history in InfluxDB is unaffected). An
invalid identifier is rejected at startup.

## Upgrading

```bash
docker compose pull
docker compose up -d
```

Database migrations run automatically on startup. Pin a specific version with
`JMONITOR_VERSION` in `.env` if you prefer explicit upgrades. If an upgrade
ships a long migration, the app container may exceed its health grace period
and `docker compose up -d` will wait on the worker; re-run `docker compose up -d`
once the app is healthy again.

## Backup

All state lives in three named volumes: `mysql-data`, `influxdb-data`,
`redis-data` (losing the Redis one drops sessions and any queued-but-unprocessed
messages — alerts/emails in flight — but no persistent data). Consistent dumps:

```bash
docker compose exec -T mysql sh -c 'mysqldump -p"$MYSQL_ROOT_PASSWORD" jmonitor' > jmonitor-mysql.sql
docker compose exec -T influxdb sh -c 'rm -rf /tmp/backup && influx backup /tmp/backup -t "$DOCKER_INFLUXDB_INIT_ADMIN_TOKEN" 1>&2 && tar -C /tmp -cf - backup' > jmonitor-influxdb.tar
```

(`-T` disables the pseudo-TTY whose ONLCR translation corrupts redirected binary output; `rm -rf` prevents stale accumulation; `1>&2` keeps influx progress output out of the tar stream.)

## Troubleshooting

- **`docker compose logs app` shows "Invalid configuration"** — a `CHANGE_ME`
  placeholder or invalid secret remains in `.env`; the message lists which.
- **404 or wrong page on every host** — your proxy does not forward the `Host`
  header (`proxy_set_header Host $host;` for nginx). The app routes by host name.
- **Login loops / mixed-content errors** — the proxy must forward
  `X-Forwarded-Proto` so the app generates `https://` URLs.
- **Dashboards don't refresh in real time** — SSE buffered by the proxy: see
  the `proxy_buffering off` / `proxy_read_timeout` lines in the nginx example.
- **InfluxDB UI** — not published by default; `http://<host>:8086` inside the
  Docker network, or publish the port in a compose override
  (login `admin` / `INFLUXDB_ADMIN_PASSWORD`).
- **First start ran with placeholder values** — MySQL and InfluxDB initialize
  their volumes with whatever credentials the first boot provided; fixing
  `.env` afterwards is not enough. Wipe the volumes and start over:
  `docker compose down -v` (safe at this stage — no real data exists yet).
