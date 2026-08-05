# Self-hosting JMonitor

[JMonitor](https://jmonitor.io) is an easy-to-use monitoring tool for PHP web
stacks: it turns your server metrics (PHP, MySQL, Redis, Nginx…) into clear
dashboards and alerts. See [jmonitor.io](https://jmonitor.io) for an overview,
try the [live demo](https://dash.jmonitor.io) (`demo@jmonitor.io` / `demo`),
or browse the source on [GitHub](https://github.com/jmonitor/jmonitor)
(AGPL-3.0).

This guide runs your own instance with Docker Compose, using the prebuilt image
from Docker Hub ([`jmonitor/jmonitor`](https://hub.docker.com/r/jmonitor/jmonitor),
also mirrored on `ghcr.io/jmonitor/jmonitor`). Deploying through a container
platform instead (Coolify, CapRover…)? See the
[container platform guide](https://github.com/jmonitor/jmonitor/blob/master/docs/paas.md).
The stack:

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
- For HTTPS, the simplest path is built in: the stack serves HTTPS itself with
  automatic Let's Encrypt certificates (see step 3) — it just needs ports 80
  and 443. Alternatives: your own reverse proxy (Caddy, nginx, Traefik…),
  Cloudflare's proxy, or plain HTTP for local tests and private networks.

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
the HTTPS step entirely.

## 2. Configure

```bash
mkdir jmonitor && cd jmonitor
curl -fsSLO https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/compose.yaml
curl -fsSLO https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/compose.https.yaml
curl -fsSL -o .env https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/.env.example
```

Edit `.env`: set `APP_DOMAIN`, replace every `CHANGE_ME` (generate secrets with
`openssl rand -hex 32`) and set the initial admin credentials.

For alert and error emails, also set your SMTP DSN (see
[Email & error reporting](#email--error-reporting)).

Set `TZ` to your timezone now rather than later (see [Timezone](#timezone)).

The app refuses to start while a `CHANGE_ME` placeholder remains.

## 3. HTTPS

Pick one of the options below. HTTPS is recommended: credentials travel over
these hosts (see "No TLS at all?" for what plain HTTP means).

### Native HTTPS — recommended, nothing to install

The stack serves HTTPS itself: the Caddy server already inside the app
container obtains and renews **Let's Encrypt certificates automatically** (one
per host) and redirects HTTP to HTTPS. It needs ports **80 and 443** free on
the host and reachable from the internet, plus the DNS records from step 1.
Enable it in `.env`:

```
COMPOSE_FILE=compose.yaml:compose.https.yaml
```

That's it — skip to step 4. In this mode the stack binds ports 80/443 directly
(`JMONITOR_HTTP_PORT` / `JMONITOR_HTTP_BIND` are ignored).

**Private network or no public domain?** Let's Encrypt cannot reach you;
keep the `COMPOSE_FILE` line above and **also** add:

```
JMONITOR_TLS=tls internal
```

(`JMONITOR_TLS` has no effect without the `COMPOSE_FILE` line — the stack
would silently stay HTTP-only.)

Certificates are then signed by Caddy's own local CA. Browsers warn until they
trust that CA — export it and install it on your machines:

```bash
docker compose cp app:/data/caddy/pki/authorities/local/root.crt jmonitor-ca.crt
```

**Bringing your own certificates** (corporate CA, wildcard you already own):
mount them into the `app` service via a compose override and set
`JMONITOR_TLS=tls /certs/cert.pem /certs/key.pem` in `.env`.

### Your own reverse proxy

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

### Cloudflare

If your DNS is on Cloudflare, you can skip running a proxy
altogether: enable the proxy ("orange cloud") on the three records and
Cloudflare terminates TLS at its edge. Set the SSL/TLS mode to *Flexible* (or
*Full* with a Cloudflare origin certificate, to also encrypt the
Cloudflare→server leg), expose the stack on port 80 (`JMONITOR_HTTP_PORT=80`,
`JMONITOR_HTTP_BIND=0.0.0.0` in `.env`), and ideally firewall the port to
[Cloudflare's IP ranges](https://www.cloudflare.com/ips/).

### No TLS at all?

The stack works fully over plain HTTP — real-time dashboard updates included:
the browser subscribes to the hub on whatever origin it is already browsing.
Credentials do travel unencrypted though, so keep that for local tests and
private networks, not for anything reachable from the internet.

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
You'll already see metrics: the stack monitors itself out of the box (see
[Self-monitoring](#self-monitoring)).

## 5. Monitor your own servers

To monitor **your own servers and apps**, create a project in the dashboard,
grab its API key, and install a collector in the PHP project(s) you want to
monitor — it's a Composer package that reports the whole stack around the app
(PHP, MySQL, Redis, web server…):

- [jmonitor/collector](https://github.com/jmonitor/collector) — for any PHP project
- [jmonitor/jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) — Symfony integration

By default the collector sends its metrics to the cloud service
(`collector.jmonitor.io`). To point it at **your** instance instead, set one
environment variable in the monitored app:

```
JMONITOR_COLLECTOR_URL=https://collector.<APP_DOMAIN>
```

In a Symfony app, add that line to the `.env` file. Anywhere else, set it as a
regular environment variable — it must be visible to the process that runs the
collector (cron job, systemd service, container…). Use `http://` instead if
your instance runs without TLS.

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

All application state lives in three named volumes: `mysql-data`,
`influxdb-data`, `redis-data` (losing the Redis one drops sessions and any
queued-but-unprocessed messages — alerts/emails in flight — but no persistent
data). Consistent dumps:

```bash
docker compose exec -T mysql sh -c 'mysqldump -p"$MYSQL_ROOT_PASSWORD" jmonitor' > jmonitor-mysql.sql
docker compose exec -T influxdb sh -c 'rm -rf /tmp/backup && influx backup /tmp/backup -t "$DOCKER_INFLUXDB_INIT_ADMIN_TOKEN" 1>&2 && tar -C /tmp -cf - backup' > jmonitor-influxdb.tar
```

(`-T` disables the pseudo-TTY whose ONLCR translation corrupts redirected binary output; `rm -rf` prevents stale accumulation; `1>&2` keeps influx progress output out of the tar stream.)

In native HTTPS mode there are two more volumes: `caddy-data` (certificates
and Let's Encrypt account) and `caddy-config`. Nothing irreplaceable —
certificates are re-issued automatically if lost — but including `caddy-data`
in your backups avoids re-issuing them on every restore, which can hit
Let's Encrypt rate limits.

## Troubleshooting

- **`docker compose logs app` shows "Invalid configuration"** — a `CHANGE_ME`
  placeholder or invalid secret remains in `.env`; the message lists which.
- **404 or wrong page on every host** — your proxy does not forward the `Host`
  header (`proxy_set_header Host $host;` for nginx). The app routes by host name.
- **Login loops / mixed-content errors** — the proxy must forward
  `X-Forwarded-Proto` so the app generates `https://` URLs.
- **Native HTTPS: no certificate, connection refused/reset on 443** — Let's
  Encrypt could not validate the domains: check the DNS records and that ports
  80/443 are open from the internet, then look at
  `docker compose logs app | grep -i acme`. Certificates are stored in the
  `caddy-data` volume once issued.
- **Set `JMONITOR_TLS` but the stack stays on HTTP** — `JMONITOR_TLS` has no
  effect without the native-HTTPS overlay: also add
  `COMPOSE_FILE=compose.yaml:compose.https.yaml` to `.env`, then re-run
  `docker compose up -d`.
- **Dashboards don't refresh in real time** — behind a reverse proxy, server-sent
  events must not be buffered: see the `proxy_buffering off` /
  `proxy_read_timeout` lines in the nginx example. The proxy must also pass
  `/.well-known/mercure` through to the stack like any other path.
- **InfluxDB UI** — not published by default; `http://<host>:8086` inside the
  Docker network, or publish the port in a compose override
  (login `admin` / `INFLUXDB_ADMIN_PASSWORD`).
- **First start ran with placeholder values** — MySQL and InfluxDB initialize
  their volumes with whatever credentials the first boot provided; fixing
  `.env` afterwards is not enough. Wipe the volumes and start over:
  `docker compose down -v` (safe at this stage — no real data exists yet).
