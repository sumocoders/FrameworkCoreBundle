# FrameworkCoreBundle Documentation

`FrameworkCoreBundle` is a Symfony bundle providing shared scaffolding for SumoCoders projects: page titles, breadcrumbs, menus, forms, file/image uploads, audit logging, pagination, and a frontend design system.

Install via the [application-skeleton](https://github.com/sumocoders/application-skeleton). PHP ^8.5, Symfony ^8.0, Doctrine ^3.3.

---

## Subsystems

| Doc | What it does |
|-----|-------------|
| [breadcrumb.md](breadcrumb.md) | `#[Breadcrumb]` attribute — builds breadcrumb trails from controller annotations |
| [title.md](title.md) | `#[Title]` attribute — sets the page `<title>` and `<h1>` |
| [audit-trail.md](audit-trail.md) | `#[AuditTrail]` attribute — logs entity creates/updates/deletes |
| [pagination.md](pagination.md) | `Paginator` — wraps a Doctrine QueryBuilder for paginated results |
| [menu.md](menu.md) | `MenuBuilder` + `ConfigureMenuEvent` — KnpMenu-based navigation |
| [forms.md](forms.md) | Custom form types (`ImageType`, `FileType`, `BelgiumPostCodeType`) and type extensions |
| [uploading-files.md](uploading-files.md) | `AbstractFile` + DBAL type — file upload value objects wired to Doctrine |
| [uploading-images.md](uploading-images.md) | `AbstractImage` + DBAL type — image upload value objects with fallback support |
| [encrypted.md](encrypted.md) | `EncryptedDBALType` — transparent field-level encryption via libsodium |
| [mails.md](mails.md) | Bundle email base template and async dispatch pattern |
| [using-date-pickers.md](using-date-pickers.md) | Date/time picker form type extensions |
| [button-locations.md](button-locations.md) | Toolbar and form submit button placement conventions |
| [language-switch.md](language-switch.md) | Multi-locale navigation switcher |
| [installation.md](installation.md) | Frontend asset installation |
| [frontend-development.md](frontend-development.md) | SCSS variables, dark mode, JS components |
| [ajax-client.md](ajax-client.md) | Axios-based AJAX client with CSRF and toast support |
| [asset-mapper.md](asset-mapper.md) | Adding CSS/JS packages via Symfony AssetMapper |
| [dark-mode.md](dark-mode.md) | Dark mode support and how to disable it |
| [stimulus.md](stimulus.md) | Stimulus controllers provided by the bundle |
| [no-results.md](no-results.md) | Standard empty-state / no-results UI component |

---

## Architecture

### Request lifecycle

```
HTTP request
  └─ kernel.controller_arguments (priority -1)
       ├─ BreadcrumbListener — reads #[Breadcrumb] from class + method, populates BreadcrumbTrail
       └─ TitleListener      — reads #[Title] from method, writes PageTitle
                               Falls back to BreadcrumbTrail if no #[Title] present
```

### Key injectable services

| Class | Purpose | Inject as |
|-------|---------|-----------|
| `SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail` | Current request breadcrumbs (iterable) | `BreadcrumbTrail $breadcrumbTrail` |
| `SumoCoders\FrameworkCoreBundle\Service\PageTitle` | Computed page title string | `PageTitle $pageTitle` |
| `SumoCoders\FrameworkCoreBundle\Service\Fallbacks` | Global config fallbacks (site title etc.) | `Fallbacks $fallbacks` |
| `SumoCoders\FrameworkCoreBundle\Menu\MenuBuilder` | KnpMenu factory; dispatches `ConfigureMenuEvent` | `MenuBuilder $menuBuilder` |
| `SumoCoders\FrameworkCoreBundle\Pagination\Paginator` | Paginates a `QueryBuilder` | constructor injection |
| `SumoCoders\FrameworkCoreBundle\Logger\AuditLogger` | Writes audit log entries | `AuditLogger $auditLogger` |

### PHP attributes

| Attribute | Target | What it does |
|-----------|--------|-------------|
| `#[Breadcrumb]` | method / class | Adds one crumb to the trail; repeatable; supports `parent:` chaining |
| `#[Title]` | method | Explicit page title with `{param}` interpolation |
| `#[AuditTrail]` | entity class | Enables Doctrine audit logging |
| `#[SensitiveData]` | entity property | Masks value in audit log as `*****` |

### Service configuration

All services are registered in `config/services.php` using PHP-format DI config. Autowiring and autoconfiguration are enabled. `Configuration.php` is intentionally empty — no runtime bundle config is needed.

---

## External packages (not documented here)

These packages are commonly used alongside this bundle. Consult their own documentation:

| Package | Docs |
|---------|------|
| `symfony/ux-autocomplete` | [Symfony UX Autocomplete](https://symfony.com/bundles/ux-autocomplete/current/index.html) |
| `doctrine/doctrine-fixtures-bundle` | [DoctrineFixturesBundle](https://symfony.com/bundles/DoctrineFixturesBundle/current/index.html) |
| `doctrine/doctrine-migrations-bundle` | [DoctrineMigrationsBundle](https://symfony.com/bundles/DoctrineMigrationsBundle/current/index.html) |
| `dreadnip/chrome-pdf-bundle` | [chrome-pdf-bundle on GitHub](https://github.com/sanderdlm/chrome-pdf-bundle) |
| Deployer | Deployment config is application-specific — see your project's `deploy.php` |
