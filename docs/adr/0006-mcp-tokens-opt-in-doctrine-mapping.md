# MCP tokens: opt-in Doctrine mapping, always-registered services

The `McpToken` entity belongs to the application's security user, and the bundle does not know that class: Automator
uses `SumoCoders\OAuthBundle\Entity\User`, other projects their own `App\...\User`. The relation targets
`UserInterface`, and `mcp_tokens.user_class` resolves it through Doctrine's `resolve_target_entities`.

Without a `user_class` that relation can't resolve. Mapping the entity anyway would break `doctrine:schema:validate`,
`make:migration` and the proxy cache warmer in every application that doesn't use MCP. So the mapping is conditional:
`SumoCodersFrameworkCoreExtension::prepend()` adds the `resolve_target_entities` entry and a separate
`FrameworkCoreBundleMcp` mapping for `src/Mcp/Entity` only when `user_class` is set. The entity can't live in
`src/Entity/` because the base mapping scans that directory recursively.

The services (repository, authenticator, voter, handlers) are always registered, in line with ADR-0003: no
`hasDefinition()`/`removeDefinition()` container surgery, the service graph doesn't depend on config. Unused they
cost nothing. Used without `user_class`, the repository throws Doctrine's "Could not find the entity manager"
error, which points at the missing mapping.

Rejected alternatives:

- Always map the entity and let each application configure `resolve_target_entities`: breaks applications that
  don't use MCP until they add config for a feature they don't use.
- A bundle interface the user class must implement: `SumoCoders\OAuthBundle\Entity\User` lives in a package that
  doesn't depend on this bundle, so it can't implement it.

Scope: only the core moved into the bundle (entity, repository, authenticator, voter, messages, handlers). Each
application keeps its own controllers, templates and translations. Tokens have no expiry date, on request.
