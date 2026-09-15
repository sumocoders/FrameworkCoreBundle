# Mistakes log

Newest first.

## CLAUDE.md claimed a Doctrine `MATCH ... AGAINST` extension that doesn't exist (found 2026-09-11)

`CLAUDE.md` documented a "Doctrine extension" subsystem at `src/Extensions/Doctrine/MatchAgainst.php`, a custom
DQL function for MySQL full-text search. A repo-wide search (class name, `MATCH ... AGAINST`, `MATCH(`, a
`src/Extensions/` directory) found no such file anywhere in the repo — removed from `CLAUDE.md`. Root cause
unknown (possibly planned but never built, or removed without updating the doc). Rule: same as below — verify a
documented subsystem's file path actually exists before trusting a summary table.

## CLAUDE.md described an unmerged branch's architecture as current (found 2026-09-11)

`CLAUDE.md`'s "Request lifecycle" section claimed `BreadcrumbListener`/`TitleListener` hook
`kernel.controller_arguments` (Symfony 8.1 per-attribute events). The actual code in `config/services.php` tags
both listeners on the generic `kernel.controller` event with manual `ReflectionClass` lookups — the older
architecture. The per-attribute-event rewrite exists only on the unmerged branch
`feature/symfony-8.1-controller-attribute-events` (commit `8676a13`), which is not an ancestor of `master`.

Root cause: a commit that updated `CLAUDE.md` to describe that branch's intended design was made assuming the
branch would land; it never did, and the doc was never reverted.

Rule: when a doc describes "how X currently fires/wires", cross-check the claim against `config/services.php` (or
the actual tag/listener registration) — not just the listener class's own doc comments — before trusting it.
Fixed in `CLAUDE.md` on 2026-09-11.
