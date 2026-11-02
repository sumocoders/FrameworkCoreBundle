# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## About this bundle

`FrameworkCoreBundle` is a Symfony 8 bundle used as a shared foundation across SumoCoders projects. It is not a
standalone application. It is installed into projects via `sumocoders/application-skeleton`.

Requirements:

- PHP ^8.5
- Symfony ^8.0,
- Doctrine ^3.3.

## Commands

```bash
# Install dependencies
symfony composer install

# Run tests
symfony php vendor/bin/phpunit

# Run a single test file
symfony php vendor/bin/phpunit tests/path/to/FooTest.php
```

No phpstan or phpcs config exists in this repo. Those are configured per-project in the consuming application.

## Architecture

### Request lifecycle

Four event listeners fire on every (main) request:

1. `SentryUserContextListener` (`kernel.request`): when a Sentry hub is registered, attaches the authenticated
   user's identifier — and, when impersonating, the impersonator's identifier — to the Sentry scope. No-ops
   otherwise (no hub, no authenticated user, sub-request).
2. `BreadcrumbListener` (`kernel.controller_arguments`, priority -1): reflects the matched controller method for
   `#[Breadcrumb]` attributes and populates `BreadcrumbTrail`.
3. `TitleListener` (`kernel.controller_arguments`, priority -1): reflects the matched controller method for
   `#[Title]` attributes and writes to `PageTitle`. Falls back to breadcrumbs if no `#[Title]` is present.
4. `MetaContextListener` (`kernel.controller_arguments`, priority -2): looks for an argument whose class carries
   `#[HasMeta]` and whose slug matches the route parameter, and pushes its `Meta` into `MetaContext`. Runs after
   `TitleListener` on purpose, so an explicit SEO page title wins over `#[Title]`.

`BreadcrumbListener` and `TitleListener` hook the generic `kernel.controller_arguments` event and manually reflect
the resolved controller (see `config/services.php`). They read `$event->getNamedArguments()`, so placeholders get
arguments the resolver already loaded — neither listener holds an `EntityManager` or does its own lookup, and each
reflects only the method that matched the route. A migration to Symfony 8.1's dedicated per-attribute events
(`kernel.controller_arguments.<FQCN>`, dropping the manual reflection) was prototyped on
`feature/symfony-8.1-controller-attribute-events` but never merged; this file previously described that unmerged
branch's design as if it were current. Correct as of 2026-10-30 — don't reintroduce that description until the
branch actually lands.

`PageTitle` and `BreadcrumbTrail` are request-scoped services aliased for direct injection.

### Attribute-driven configuration

Controller behaviour is controlled via PHP 8 attributes, not YAML or annotations:

| Attribute          | Target          | Purpose                                                                                            |
|--------------------|-----------------|----------------------------------------------------------------------------------------------------|
| `#[Breadcrumb]`    | method / class  | Adds one crumb; repeatable for chains; supports `parent:` for automatic trail building             |
| `#[Title]`         | method / class  | Explicit page title; supports `{param}` / `{object.property}` interpolation and `parent:` chaining |
| `#[AuditTrail]`    | entity class    | Enables Doctrine audit logging for that entity; `withData: false` skips field-level diff           |
| `#[SensitiveData]` | entity property | Masks property value in audit log output                                                           |
| `#[HasMeta]`       | entity class    | Marks an entity whose embedded `Meta` feeds the page's SEO tags; `meta:` / `slug:` name the getters |

For invokable controllers, prefer placing `#[Route]`, `#[Breadcrumb]`, and `#[Title]` on the class rather than on
`__invoke`.

### Subsystems

- **Pagination** (`src/Pagination/Paginator.php`): wraps a Doctrine `QueryBuilder`; exposes current page, total
  results, prev/next. See `docs/pagination.md`.
- **Menu** (`src/Menu/MenuBuilder.php`): KnpMenu builder dispatching `ConfigureMenuEvent`; consuming apps listen to
  that event to add items. See `docs/menu.md`.
- **Quick search** (`src/Service/QuickSearch/`, `src/Twig/Components/QuickSearch.php`): Cmd+K modal in
  `base.html.twig`, a live component over tagged `QuickSearchProvider`s (the bundle ships the menu provider). See
  `docs/quick-search.md`.
- **SEO** (`src/Entity/Meta.php`, `src/Attribute/HasMeta.php`, `src/Service/MetaContext.php`,
  `src/Form/Type/SeoType.php`, `src/Twig/{MetaExtension,SchemaExtension}.php`): per-entity meta tags via an
  `#[ORM\Embeddable]` `Meta`, rendered by `meta_tags()` on the entity's slug-based public page only. See
  `docs/seo.md`.
- **Audit trail** (`src/DoctrineListener/DoctrineAuditListener.php`): Doctrine `onFlush` + `postPersist` listener that
  logs creates/updates/deletes via `AuditLogger`. Entities opt in with `#[AuditTrail]`. See `docs/audit-trail.md`.
- **Sentry user context** (`src/EventListener/SentryUserContextListener.php`): attaches the authenticated (and
  impersonator) user identifier to the Sentry scope. Always active when a Sentry hub is registered — no
  configuration option to disable it. See `docs/sentry-user-context.md`.
- **Forms** (`src/Form/`): custom types (`ImageType`, `FileType`, `BelgiumPostCodeType`) and extensions wiring date
  pickers, toggle-password, and collection UI. See `docs/forms.md`.
- **DBAL types** (`src/DBALType/`): `EncryptedDBALType`, `AbstractImageType`, `AbstractFileType` for custom column
  handling. See `docs/encrypted.md`, `docs/uploading-files.md`, `docs/uploading-images.md`.
- **Twig** (`src/Twig/`): `FrameworkExtension`, `PaginatorExtension`, `ContentExtension`; `PageTitle` is available as a
  string in templates.

### Service registration

All services are registered in `config/services.php` (PHP-format DI config, no YAML). The bundle extension
(`src/DependencyInjection/SumoCodersFrameworkCoreExtension.php`) loads that file. `Configuration.php` is
currently an empty tree — the bundle defines no config options.

## Documentation

All docs live in `docs/`. Start at `docs/index.md` for an overview of all subsystems, the request lifecycle, and key
injectable services.

When adding or changing a subsystem, update the corresponding doc file in `docs/`. Each doc follows the format: purpose,
prerequisites, usage, options table, examples, troubleshooting — followed by an `## Internals` section covering
control flow, gotchas, and known limitations for people working on the bundle itself.

Internal/control-flow documentation previously lived in a separate `docs/wiki/` tree generated by the
`project-wiki` skill; as of 2026-09-14 that tree was merged into the matching `docs/*.md` file's `## Internals`
section and removed. Keep documenting both usage and internals directly in `docs/*.md` — don't recreate
`docs/wiki/`.
