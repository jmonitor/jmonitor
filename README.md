# JMonitor

JMonitor is an easy-to-use monitoring tool for PHP web stacks: it turns your server metrics (MySQL, Redis, Nginx, PHP…) into clear dashboards and alerts.

It comes in two editions: a self-hostable version, and a hosted service at [jmonitor.io](https://jmonitor.io).

[![CI](https://github.com/jmonitor/jmonitor/actions/workflows/ci.yml/badge.svg)](https://github.com/jmonitor/jmonitor/actions/workflows/ci.yml)
[![License: AGPL-3.0](https://img.shields.io/badge/license-AGPL--3.0-blue.svg)](LICENSE)

<table>
  <tr>
    <th valign="top">jmonitor/jmonitor<br><img src="https://img.shields.io/badge/you_are_here-0969da?style=flat-square" alt="you are here"></th>
    <th valign="top"><a href="https://github.com/jmonitor/collector">jmonitor/collector</a><br>&nbsp;</th>
    <th valign="top"><a href="https://github.com/jmonitor/jmonitor-bundle">jmonitor/jmonitor-bundle</a><br>&nbsp;</th>
  </tr>
  <tr>
    <td>Self-hostable backend — not needed with the cloud version</td>
    <td>The collectors — install them in the project to monitor</td>
    <td>Symfony-specific integration of the collectors</td>
  </tr>
  <tr>
    <td colspan="3" align="center">
      <a href="https://jmonitor.io">Website and cloud edition</a> ·
      <a href="https://hub.docker.com/r/jmonitor/jmonitor">Docker Hub image for self-hosting</a>
    </td>
  </tr>
</table>

<img src=".github/assets/hero-dashboard.png" alt="JMonitor dashboard" width="700">

## Demo

Try it at [dash.jmonitor.io](https://dash.jmonitor.io) — log in with `demo@jmonitor.io` / `demo`.

It's a read-only viewer account on a demo project fed with synthetic metrics, so you can browse every dashboard without installing anything.

## Features

JMonitor focuses on getting you readable dashboards fast, made for the PHP world.

- **Dashboards** — one page per component, with gauges, charts and version badges. Real-time updates over Mercure.
- **Alerts** — threshold alerts on key metrics, end-of-life / outdated version checks.
- **Notifications** — email notifications when an alert triggers.
- **Multi-project, multi-user** — projects with owner/admin/viewer roles.
- **Lightweight agent** — a small PHP collector pushes metrics over HTTPS; nothing to install besides Composer packages.

## Supported components

| Category          | Components                                                                                                                                                                                                                                                                                                |
| ----------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Runtime           | ![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white) ![FrankenPHP](https://img.shields.io/badge/FrankenPHP-444444?style=flat-square)                                                                                                                                  |
| Framework         | ![Symfony](https://img.shields.io/badge/Symfony-000000?style=flat-square&logo=symfony&logoColor=white)                                                                                                                                                                                                     |
| Web servers       | ![Apache](https://img.shields.io/badge/Apache-D22128?style=flat-square&logo=apache&logoColor=white) ![Nginx](https://img.shields.io/badge/Nginx-009639?style=flat-square&logo=nginx&logoColor=white) ![Caddy](https://img.shields.io/badge/Caddy-1F88C0?style=flat-square&logo=caddy&logoColor=white)        |
| Databases & cache | ![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white) ![PostgreSQL](https://img.shields.io/badge/PostgreSQL-4169E1?style=flat-square&logo=postgresql&logoColor=white) ![Redis](https://img.shields.io/badge/Redis-FF4438?style=flat-square&logo=redis&logoColor=white) |
| System            | ![Linux](https://img.shields.io/badge/CPU%20·%20RAM%20·%20Disk-FCC624?style=flat-square&logo=linux&logoColor=black)                                                                                                                                                                                        |

## How it works

The collectors are written in PHP and run within your application environment: [jmonitor/collector](https://github.com/jmonitor/collector) is a regular Packagist package ([jmonitor/jmonitor-bundle](https://github.com/jmonitor/jmonitor-bundle) for drop-in Symfony integration).

1. **Install** — add the collector to your app via Composer.
2. **Run** — launch the provided PHP worker. On Symfony apps, it's a console command.
3. **Collect** — metrics are periodically gathered from your environment (web server, database, PHP…).
4. **Visualize** — metrics are pushed over HTTPS to JMonitor — either [jmonitor.io](https://jmonitor.io) (cloud edition) or your own self-hosted instance — where they're stored for dashboards and alerting.

This repository is the server application: it ingests the metrics, stores time series in InfluxDB and relational data in MySQL, renders the dashboards and evaluates alerts.

## Self-hosting

> [!TIP]
> Not sure which edition you need? Try the cloud one first: every new project starts with **7 days of the Pro plan**, no credit card asked. Once the trial is over the project falls back to the Free plan, which never expires — and if you'd rather host it yourself, switching is a matter of pointing the collector at your own instance.

JMonitor is free to self-host: the full stack (app, worker, MySQL, Redis, InfluxDB) runs from a prebuilt Docker image published on Docker Hub ([`jmonitor/jmonitor`](https://hub.docker.com/r/jmonitor/jmonitor)). See the **[self-hosting guide](docker/selfhosted/README.md)** — or the **[container platform guide](docs/paas.md)** to deploy through Coolify and similar PaaS.

## Stack

- PHP 8.4, Symfony 8, Doctrine ORM
- MySQL (application data), InfluxDB 2.x (time series), Redis (cache, sessions, rate limiting)
- Mercure (real-time dashboard updates)
- Bootstrap 5 + Stimulus + Turbo + Chart.js, served with Symfony AssetMapper (no Node.js build)

## Contributing

Development setup, quality checks and guidelines live in [CONTRIBUTING.md](CONTRIBUTING.md). To report a vulnerability, see [SECURITY.md](SECURITY.md).

## License

JMonitor is open source under the [AGPL-3.0](LICENSE) license.
