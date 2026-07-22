# Running JMonitor on a container platform (Coolify, CapRover…)

The [self-hosting guide](../docker/selfhosted/README.md) assumes a shell on the
server: download the compose files, edit `.env`, `docker compose up -d`.
Container platforms (PaaS) can deploy the same stack from their web UI instead —
provided they actually run the Docker Compose file rather than re-interpreting
it. Quick verdict:

| Platform | Compatible? | How |
|---|---|---|
| **Coolify** | Yes | Compose-based deployment, see below |
| **Dokploy**, **Portainer** (stacks) | Expected to work | They run real compose files — see the checklist below |
| **CapRover** | Not as-is | Its compose parser drops fields the stack relies on, see below |

None of these paths is covered by our CI (the reference install is the Docker
Compose CLI) — if you hit a platform-specific problem, please
[open an issue](https://github.com/jmonitor/jmonitor/issues).

## What the stack needs from a platform

[`compose.yaml`](../docker/selfhosted/compose.yaml) is a plain multi-service
Compose file, nothing exotic — but the platform must:

1. **Run the compose file as-is.** The stack relies on `command` overrides
   (worker and collector are the same image as the app, started with different
   commands), `env_file: .env`, YAML anchors, healthchecks and `depends_on`
   conditions. Platforms that translate compose into their own model instead of
   running Docker Compose usually lose some of these.
2. **Provide environment variables both ways**: for `${...}` interpolation in
   the compose file, *and* as a generated `.env` file next to it — the app,
   worker and collector containers read their configuration through
   `env_file: .env`.
3. **Route three domains to the app service** (container port 80):
   `dash.<domain>`, `collector.<domain>`, `admin.<domain>`. The proxy must
   preserve the `Host` header (the app routes by host name) and set
   `X-Forwarded-Proto`.
4. **Not buffer Server-Sent Events** — the real-time dashboard updates use
   long-lived SSE connections. Traefik and Caddy pass them through by default;
   nginx-based proxies need `proxy_buffering off`.

TLS is terminated by the platform's proxy: deploy the base `compose.yaml`
**only** — never add `compose.https.yaml`, ports 80/443 belong to the
platform.

## Coolify

Coolify deploys compose files natively and meets all the requirements above.

1. **Add the resource** — *+ New* → *Docker Compose Empty*, and paste the
   contents of
   [`compose.yaml`](https://raw.githubusercontent.com/jmonitor/jmonitor/master/docker/selfhosted/compose.yaml).
2. **Set the environment variables.** Coolify auto-detects the variables
   referenced in the compose file (`APP_DOMAIN`, `MYSQL_ROOT_PASSWORD`,
   `INFLUXDB_TOKEN`, `INFLUXDB_ADMIN_PASSWORD`, `TZ`…) and flags the required
   ones. Add the remaining ones by hand — they are read from the generated
   `.env`, not interpolated, so Coolify cannot detect them: `APP_SECRET`,
   `MERCURE_JWT_SECRET`, `ADMIN_EMAIL`, `ADMIN_PASSWORD`, plus the optional
   mail settings (`MAILER_DSN`, `MAILER_SENDER_EMAIL`, `ERROR_MAIL_TO`).
   [`.env.example`](../docker/selfhosted/.env.example) is the reference list,
   with a comment per variable. Tick **Is Literal?** on the secrets so a `$`
   in a generated value is not mangled by interpolation.
3. **Assign the domains** — on the **app** service, declare the three domains
   with `https://`: `https://dash.<domain>`, `https://collector.<domain>`,
   `https://admin.<domain>`. Coolify's proxy obtains the certificates; the DNS
   records point at the Coolify server (step 1 of the
   [self-hosting guide](../docker/selfhosted/README.md)).
4. **Deploy.** The first start runs the migrations and creates the admin
   account, then log in on `https://dash.<domain>`. A missing or placeholder
   variable stops the app with an explicit "Invalid configuration" message in
   the app service's logs, naming the variable to fix.

Notes:

- The `ports:` mapping of the app service binds `127.0.0.1:8080` on the
  Coolify server. It is harmless (loopback only) and unused — Coolify's proxy
  reaches the container over the Docker network. Remove the `ports:` block if
  you prefer, or change `JMONITOR_HTTP_PORT` if 8080 is taken.
- `TRUSTED_PROXIES` needs no change: the default trusts private network
  ranges, which covers the platform's proxy.
- To monitor your other apps, nothing platform-specific: create a project,
  then set `JMONITOR_COLLECTOR_URL=https://collector.<domain>` in the
  monitored app (see the self-hosting guide, step 5).

## CapRover

**The stack cannot be deployed on CapRover as-is.** CapRover does not run
Docker Compose: it re-parses compose files through the Docker API and only
understands `image`, `environment`, `ports`, `volumes`, `depends_on` and
`hostname` ([their docs](https://caprover.com/docs/docker-compose.html)).
Everything else is silently dropped — including two fields the stack depends
on:

- `command` — the worker and collector would boot as web servers instead of
  running `messenger:consume` / `jmonitor:collect`: no metric ingestion, no
  alerts;
- `env_file` — the containers would start without your configuration.

Deploying anyway means hand-building six separate CapRover apps (one per
service) with per-app Dockerfiles to restore the start commands, rewriting
every service hostname to CapRover's `srv-captain--` names, attaching the
three custom domains to the app, and disabling nginx buffering for SSE. It is
possible but heavy, untested and unsupported.

## Other platforms

Any platform that runs real Compose files (Dokploy, Portainer's
Docker-Compose stacks — but not its Swarm-mode stacks: `docker stack deploy`
ignores parts of the file, such as `depends_on` conditions) should work with
the same recipe as Coolify: paste or reference `compose.yaml`, provide the
[`.env.example`](../docker/selfhosted/.env.example) variables through the
platform's environment UI, route the three domains to the app service's port
80, and check the four requirements at the top of this page.
