# First bundle config option, wired via container parameter + constructor flag

`sentry_user_context.enabled` (bool, default `true`) is the first real option `Configuration.php` has ever
defined — before this feature the tree was deliberately empty, and `SumoCodersFrameworkCoreExtension::load()`
never called `processConfiguration()`. This decision is as much about the *wiring mechanism* as the option
itself, since it sets the pattern any future bundle config option will likely copy.

The chosen approach: `Extension::load()` calls `processConfiguration()`, sets a single container parameter
(`sumo_coders_framework_core.sentry_user_context.enabled`), and `config/services.php` binds that parameter to
`SentryUserContextListener`'s `bool $enabled` constructor argument via `->bind('bool $enabled', param('...'))`.
The listener itself guards on `$enabled` with an early return.

The alternative — conditionally registering or removing the listener's service definition in `load()` based on
the config value — was rejected. There was no precedent anywhere in this bundle for `hasDefinition()`/
`removeDefinition()`-style conditional wiring, and the parameter+guard approach is simpler, keeps the service
graph static and easy to reason about (`debug:container` always shows the listener, just inert when disabled),
and matches how the nullable `?HubInterface` dependency is already handled — both "is this feature active"
questions collapse to a runtime no-op check inside the listener, not container surgery.

## Update: the option was removed

Shortly after introduction, `sentry_user_context.enabled` was removed entirely and the feature made always
active. In practice no consuming project needed to disable it — the listener already no-ops safely when no
Sentry hub is registered, so the toggle was config surface with no real use case. `Configuration.php` is an
empty tree again, `SumoCodersFrameworkCoreExtension::load()` no longer calls `processConfiguration()`, and
`SentryUserContextListener` no longer takes an `$enabled` constructor argument.

The parameter+bind+runtime-guard *mechanism* described above remains the pattern to follow if this bundle ever
grows a config option again (see `docs/memory/architecture.md`) — only this specific option was removed, not
the wiring approach itself.
