# Contributing to JMonitor

Thanks for your interest in contributing!

## Before you start

- **Bugs**: open an issue with steps to reproduce, expected vs. actual behavior, and your environment.
- **Features**: open an issue first to discuss the idea before investing time in a pull request.
- **Security issues**: never open a public issue — see [SECURITY.md](SECURITY.md).

## Development setup

The dev environment is the Docker stack described in the [Development setup](README.md#development-setup) section of the README.

## Quality checks

Every pull request must pass CI, which runs the equivalent of:

```bash
make cs       # PHP-CS-Fixer (@PER-CS style)
make phpstan  # static analysis
make test     # test suite
# or, inside the container (make sh):
composer run lint:check / phpstan / phpunit
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
