[Back to index](../index.md)

# Infrastructure internals

Cross-cutting internals that don't belong to a single user-facing subsystem: serializer safety nets, CSP nonce
propagation, maintenance console commands, and Doctrine extension wiring.

## Children

- [serializer.md](serializer.md) — circular-reference/max-depth handlers for the Symfony Serializer
- [nonce-generator.md](nonce-generator.md) — CSP nonce decorator for NelmioSecurityBundle
- [commands.md](commands.md) — `sumo:translate` and `sumo:maintenance:create-pr-for-outdated-dependencies`
- [doctrine-extension-listener.md](doctrine-extension-listener.md) — Gedmo Blameable/Loggable current-user wiring
  (currently unregistered — see gotcha in that entry)
- [sentry-user-context.md](sentry-user-context.md) — `SentryUserContextListener`, attaches the authenticated (and
  impersonating) user's identifier to the Sentry scope; on by default, inert without Sentry installed

## Note: no MySQL full-text search DQL function exists in this repo

CLAUDE.md documents a "Doctrine extension" subsystem at `src/Extensions/Doctrine/MatchAgainst.php` implementing a
custom DQL function for MySQL `MATCH ... AGAINST`. A repo-wide search (`MatchAgainst`, `MATCH AGAINST`, `MATCH(`,
plus a filename search for `*matchagainst*`/`*fulltext*`) found no such file, class, or DQL function
registration anywhere in this repository, including `composer.json`'s `doctrine/orm` config and `config/services.php`.
There is no `src/Extensions/` directory at all. Either this was removed at some point, lives only in a downstream
project, or the CLAUDE.md line is simply inaccurate — treat that claim as unverified/incorrect until such a file
reappears.
