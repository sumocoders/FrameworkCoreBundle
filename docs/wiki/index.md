# FrameworkCoreBundle wiki

How this bundle's subsystems actually work internally — for agents working on the bundle's own code. For
consumer-facing usage docs (how a project installs/configures each subsystem), see `docs/*.md` instead; this
wiki cross-references those but documents control flow, extension points, and gotchas rather than repeating
usage instructions. Project-wide conventions live in `docs/memory/`; decision rationale lives in `docs/adr/`.

## Full tree

- [Request lifecycle](request-lifecycle/index.md) — `kernel.controller` listeners resolving breadcrumbs/title
  - [Breadcrumb](request-lifecycle/breadcrumb.md) — `BreadcrumbListener`/`BreadcrumbTrail`, `parent:` chaining
  - [Title](request-lifecycle/title.md) — `TitleListener`/`PageTitle`, placeholder/translation resolution
- [Audit trail](audit-trail.md) — `DoctrineAuditListener`/`AuditLogger`, `#[AuditTrail]`/`#[SensitiveData]`
- [Pagination](pagination.md) — `Paginator`/`PaginatorExtension`
- [Menu](menu.md) — `MenuBuilder`/`ConfigureMenuEvent`/`DefaultMenuListener`
- [Forms](forms/index.md) — custom types and type extensions
  - [Belgium post code](forms/belgium-post-code.md) — `BelgiumPostCodeType`/`BelgiumPostCodes`/`BelgiumPostCode`
- [Uploads](uploads/index.md) — value-object + DBAL-type pairing pattern
  - [Files](uploads/files.md) — `AbstractFile`/`AbstractFileType`
  - [Images](uploads/images.md) — `AbstractImage`/`AbstractImageType`, fallback image behavior
- [Encrypted fields](encrypted-fields.md) — `EncryptedDBALType`, libsodium secretbox
- [Twig extensions](twig-extensions.md) — `FrameworkExtension`, `ContentExtension`, `AssetContentExtension`,
  `PaginatorExtension`
- [Mail](mail.md) — base mail template, CSS inlining via `asset_content()`, async dispatch
- [CRUD pattern](crud.md) — how a standard CRUD feature composes the bundle's other subsystems in a consuming
  project
- [Frontend conventions](frontend/index.md) — AJAX client, asset pipeline/SCSS, dark mode, Stimulus, no-results,
  language switch
- [Infrastructure](infrastructure/index.md) — cross-cutting internals
  - [Serializer handlers](infrastructure/serializer.md) — `CircularReferenceHandler`, `MaxDepthHandler` (opt-in,
    not wired by this bundle)
  - [Nonce generator](infrastructure/nonce-generator.md) — `NonceGenerator`, CSP nonce override
  - [Commands](infrastructure/commands.md) — `sumo:translate`, `sumo:maintenance:create-pr-for-outdated-dependencies`
  - [Doctrine extension listener](infrastructure/doctrine-extension-listener.md) — `DoctrineExtensionListener`
    (currently unregistered/dead code — see entry)

## Known doc bugs found while writing this wiki (see `docs/memory/mistakes.md` for the log)

- `CLAUDE.md`'s request-lifecycle description and its claimed `src/Extensions/Doctrine/MatchAgainst.php` — both
  fixed 2026-09-11.
- `docs/title.md` still describes `parent:` title chaining as a real subrequest; the code doesn't do that — not
  yet fixed, flagged in [request-lifecycle/title.md](request-lifecycle/title.md).
- `docs/uploading-files.md` documents `AbstractFile::__toString()` as returning the web path; the code returns
  the raw filename — not yet fixed, flagged in [uploads/index.md](uploads/index.md).
