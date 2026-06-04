# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About this bundle

`FrameworkCoreBundle` is a Symfony 8 bundle used as a shared foundation across SumoCoders projects. It is not a standalone application — it is installed into projects via `sumocoders/application-skeleton`. PHP ^8.5, Symfony ^8.0, Doctrine ^3.3.

## Commands

```bash
# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit

# Run a single test file
./vendor/bin/phpunit tests/path/to/FooTest.php
```

No phpstan or phpcs config exists in this repo — those tools are configured per-project in the consuming application.

## Architecture

### Request lifecycle

Two event listeners fire on every request:

1. `BreadcrumbListener` (`kernel.controller_arguments`, priority -1) — reads `#[Breadcrumb]` attributes from the matched controller and populates `BreadcrumbTrail`.
2. `TitleListener` (`kernel.controller_arguments`, priority -1) — reads `#[Title]` attributes and writes to `PageTitle`. Falls back to breadcrumbs if no `#[Title]` is present.

`PageTitle` and `BreadcrumbTrail` are request-scoped services aliased for direct injection.

### Attribute-driven configuration

Controller behaviour is controlled via PHP 8 attributes, not YAML or annotations:

| Attribute | Target | Purpose |
|-----------|--------|---------|
| `#[Breadcrumb]` | method / class | Adds one crumb; repeatable for chains; supports `parent:` for automatic trail building |
| `#[Title]` | method | Explicit page title; supports `{param}` / `{object.property}` interpolation and `parent:` chaining |
| `#[AuditTrail]` | entity class | Enables Doctrine audit logging for that entity; `withData: false` skips field-level diff |
| `#[SensitiveData]` | entity property | Masks property value in audit log output |

### Subsystems

- **Pagination** (`src/Pagination/Paginator.php`) — wraps a Doctrine `QueryBuilder`; exposes current page, total results, prev/next. See `docs/pagination.md`.
- **Menu** (`src/Menu/MenuBuilder.php`) — KnpMenu builder dispatching `ConfigureMenuEvent`; consuming apps listen to that event to add items. See `docs/menu.md`.
- **Audit trail** (`src/DoctrineListener/DoctrineAuditListener.php`) — Doctrine `onFlush` + `postPersist` listener that logs creates/updates/deletes via `AuditLogger`. Entities opt in with `#[AuditTrail]`. See `docs/audit-trail.md`.
- **Forms** (`src/Form/`) — custom types (`ImageType`, `FileType`, `BelgiumPostCodeType`) and extensions wiring date pickers, toggle-password, and collection UI. See `docs/forms.md`.
- **DBAL types** (`src/DBALType/`) — `EncryptedDBALType`, `AbstractImageType`, `AbstractFileType` for custom column handling. See `docs/encrypted.md`, `docs/uploading-files.md`, `docs/uploading-images.md`.
- **Twig** (`src/Twig/`) — `FrameworkExtension`, `PaginatorExtension`, `ContentExtension`; `PageTitle` is available as a string in templates.
- **Doctrine extension** (`src/Extensions/Doctrine/MatchAgainst.php`) — custom DQL function for MySQL `MATCH ... AGAINST` full-text search.

### Service registration

All services are registered in `config/services.php` (PHP-format DI config, no YAML). The bundle extension (`src/DependencyInjection/SumoCodersFrameworkCoreExtension.php`) loads that file. `Configuration.php` is intentionally empty — no runtime bundle config is needed.

## Documentation

All docs live in `docs/` (flat, no subdirectories). Start at `docs/index.md` for an overview of all subsystems, the request lifecycle, and key injectable services.

When adding or changing a subsystem, update the corresponding doc file in `docs/`. Each doc follows the format: purpose, prerequisites, usage, options table, examples, troubleshooting.
