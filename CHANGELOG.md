# Changelog

All notable changes to JMonitor are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Version numbers are the tags of the self-hosted Docker image; the cloud edition
is continuously deployed. Lines suffixed with `(cloud)` or `(self-hosted)` apply
to that edition only, everything else applies to both.

## [Unreleased]

### Added

- The dashboard shows which JMonitor version the instance runs, and whether a newer release is available (self-hosted)
- The collector status card shows whether the collector pushing metrics is up to date. Collectors older than 2.1 advertise a version that was never bumped, so their version reads as unknown and they are reported as outdated
- Symfony projects also get the version of `jmonitor/jmonitor-bundle`, checked against its own releases: the bundle carries the Symfony collectors and is released on its own schedule, so an up-to-date collector says nothing about it. The line appears once the agent advertises it
- A "What's new" card on the dashboard lists the release notes of the running version, read from its own changelog. Each edition only sees the lines that apply to it

## [1.0.0] - 2026-08-07

- Initial public release.

[Unreleased]: https://github.com/jmonitor/jmonitor/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jmonitor/jmonitor/releases/tag/v1.0.0
