[Back to index](index.md)

# CRUD pattern

Not a subsystem this bundle implements — a recipe for how a standard admin CRUD feature is composed in a *consuming*
project, built on top of several bundle subsystems: `#[Breadcrumb]`/`#[Title]` attributes, `Paginator`, and the form
layer. Full worked example with complete code for every piece: [../crud.md](../crud.md) (read that before building a
new CRUD — this page only orients where things live and why).

## Composition

A standard CRUD is four invokable controllers + one shared DataTransferObject + one form type + Messenger for
mutations, laid out (in the consuming app, under `src/` and `templates/`, not in this bundle) as:

| Piece | Conventional location (consuming project) | Notes |
|---|---|---|
| Controllers | `src/Controller/{Entity}/Admin/{Overview,Create,Update,Delete}Controller.php` | One invokable class per action; `#[Route]` + `#[Breadcrumb]` on the class, not `__invoke`. |
| DataTransferObject | `src/DataTransferObject/{Entity}/{Entity}DataTransferObject.php` | Abstract; public properties with defaults; shared by create and update. |
| Messages | `src/Message/{Entity}/{Create,Update,Delete}{Entity}Message.php` | Create/Update extend the DTO; Delete carries only the id. Dispatched via `MessageBusInterface`. |
| Message handlers | `src/MessageHandler/{Entity}/*Handler.php` | `#[AsMessageHandler]`; do the actual entity mutation + repository call. |
| Form type | `src/Form/{Entity}/{Entity}Type.php` | Single type reused for both create and update, `data_class` = the DTO. |
| Entity | `src/Entity/{Entity}.php` | Constructor-only creation; explicit `update()` method (not DTO-driven). |
| Repository | `src/Repository/{Entity}Repository.php` | Exposes a `getPaginated(): Paginator` method; `add()`/`remove()` take an optional `$flush = true`. |
| Templates | `templates/{entity}/{index,create,update}.html.twig` | Extend the app's `layout.html.twig`; use `header_navigation`/`header_actions_left`/`header_actions_right` blocks for buttons. |
| Translations | `translations/messages+intl-icu.{en,nl}.yaml` | Breadcrumb labels, field labels, flash messages, action labels, delete confirmation text. |
| Tests | `tests/MessageHandler/{Entity}/*Test.php` (unit), `tests/Controller/{Entity}/Admin/*Test.php` (functional `WebTestCase`) | Handler tests mock the repository; controller tests hit the routes. |

## Why this is a wiki page and not a bundle subsystem

None of these classes live in this bundle. What the bundle contributes to the pattern:

- `#[Breadcrumb]` / `#[Title]` attributes (`src/Attribute/`) drive the trail shown on each controller.
- `Paginator` (`src/Pagination/Paginator.php`) backs `OverviewController`'s listing + the `pagination()` Twig
  function.
- The form extensions in `src/Form/` (date pickers, collection UI, etc.) are available to `{Entity}Type` when needed.
- `#[AuditTrail]` (`src/DoctrineListener/`) is opt-in on the entity if audit logging is required for that CRUD.

Everything else (controllers, DTOs, messages, handlers, templates, translations) is hand-written per feature,
following the shapes in [../crud.md](../crud.md).

## Gotchas

- The DTO is never instantiated directly — only through `CreateXMessage`/`UpdateXMessage` subclasses — so validation
  constraints live on the abstract DTO but get exercised via the concrete message subclass the form is bound to.
- `UpdateXMessage`'s constructor pre-fills DTO properties from the entity so `$form->getData()` can be dispatched
  as-is; there is no separate mapping step from form data back to a message.
- `DeleteController` validates a CSRF-protected empty form before dispatching the delete message — deletion is never
  driven by a bare link/GET.
