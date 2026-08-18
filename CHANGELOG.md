# Changelog

All notable changes to JMonitor are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).
Version numbers are the tags of the self-hosted Docker image; the cloud edition
is continuously deployed. Lines suffixed with `(cloud)` or `(self-hosted)` apply
to that edition only, everything else applies to both.

## [Unreleased]

### Fixed

- Admin panel is now served with its CSS and JavaScript (self-hosted)
- Opening the admin panel without admin rights no longer loops the browser on redirects (self-hosted)
- The info icon of an empty metric card now opens its help panel instead of an error page
- The login page no longer links to a website the instance does not serve (self-hosted)
- Empty metric cards read "No data" instead of "No datas"
- The scheduler card no longer describes one of its tasks in French

## [1.0.0] - 2026-08-14

- Initial public release.

[Unreleased]: https://github.com/jmonitor/jmonitor/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/jmonitor/jmonitor/releases/tag/v1.0.0
