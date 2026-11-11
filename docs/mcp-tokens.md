# MCP tokens

Personal bearer tokens for an MCP endpoint (for example one built with `symfony/mcp-bundle`). A user creates a token,
pastes it in an MCP client like the Claude desktop app, and every request with that token runs as that user, with
that user's roles.

The bundle ships the core only: the `McpToken` entity, its repository, the authenticator, the revoke voter and the
create/revoke messages. Each application builds its own controllers, forms, templates and menu links on top of it.

## Prerequisites

- An MCP endpoint in the application, behind its own firewall (see the example below).
- A Doctrine entity for the application's security user. The token links to it with a `ManyToOne`.
- The feature is opt-in. Nothing gets mapped until `mcp_tokens.user_class` is set.

## Setup in a new project

The bundle doesn't ship an endpoint or a UI. A new project needs these steps, the sections below explain each one:

- [ ] **MCP endpoint**: install `symfony/mcp-bundle`, configure it in `config/packages/mcp.yaml` (server name,
  `/_mcp` path, `allowed_hosts` in prod) and import its routes in `config/routes/mcp.yaml`.
- [ ] **Bundle config**: set `mcp_tokens.user_class`, and optionally `mcp_tokens.token_prefix`
  (see [Configuration](#configuration)).
- [ ] **Migration**: run `symfony console make:migration` for the `mcp_api_token` table.
- [ ] **Security**: add the stateless `mcp` firewall above `main`, and the `access_control` rule for `/_mcp`
  (see [Firewall](#firewall)).
- [ ] **Overview page with create form**: a form with one `name` field on `CreateMcpToken`. Render the plain token
  in the response, don't redirect. List the user's tokens with `findForUser()`
  (see [Creating a token](#creating-a-token)).
- [ ] **Revoke route**: a POST route with an empty form for CSRF, `McpTokenVoter::REVOKE` check, then dispatch
  `RevokeMcpToken` (see [Revoking a token](#revoking-a-token)).
- [ ] **Admin overview** (optional): `findAllWithUser()`, guarded with `ROLE_ADMIN` in `access_control`.
- [ ] **Templates and translations**: the bundle ships no labels for this feature.
- [ ] **Menu links**: override `templates/bundles/SumoCodersFrameworkCoreBundle/usermenu.html.twig` and add the links
  in the `user_navigation` block, the admin link behind `is_granted('ROLE_ADMIN')`.
- [ ] **Messenger**: keep `CreateMcpToken` synchronous, don't route it to an async transport.
- [ ] **Claude desktop snippet** (optional): a ready-to-paste `mcpServers` entry with `npx mcp-remote`, the endpoint
  URL and the token as `AUTH_TOKEN`. Automator builds it in `ApiTokenController::buildDesktopConfig()`.

## Configuration

```yaml
# config/packages/sumo_coders_framework_core.yaml
sumo_coders_framework_core:
    mcp_tokens:
        user_class: App\Entity\User\User
        token_prefix: 'myapp_mcp_'
```

| Option                    | Default | Description                                                                         |
|---------------------------|---------|-------------------------------------------------------------------------------------|
| `mcp_tokens.user_class`   | `null`  | Security user entity that owns a token. Must implement `UserInterface`. Enables the feature. |
| `mcp_tokens.token_prefix` | `mcp_`  | Prepended to every new token, so secret scanners can recognize a leaked token.      |

Then generate a migration (`symfony console make:migration`). It creates the `mcp_api_token` table.

## Usage

### Firewall

Put the MCP path behind a stateless firewall that uses the bundle's authenticator, above the `main` firewall:

```yaml
# config/packages/security.yaml
security:
    firewalls:
        mcp:
            pattern: ^/_mcp
            stateless: true
            provider: app_user_provider
            entry_point: SumoCoders\FrameworkCoreBundle\Mcp\Security\McpTokenAuthenticator
            custom_authenticators: [ SumoCoders\FrameworkCoreBundle\Mcp\Security\McpTokenAuthenticator ]
    access_control:
        - { path: '^/_mcp(/|$)', roles: ROLE_USER }
```

A request without a valid `Authorization: Bearer <token>` header gets a `401` JSON response with a
`WWW-Authenticate: Bearer` header.

### Creating a token

Dispatch `CreateMcpToken` and read the handler's return value. The handler returns the plain token, the only moment
anyone can see it. Render it in the response directly, don't redirect: a flash message would store the token in the
session.

```php
use SumoCoders\FrameworkCoreBundle\Mcp\Message\CreateMcpToken;
use Symfony\Component\Messenger\HandleTrait;

$message = new CreateMcpToken($user);
$form = $this->createForm(McpTokenType::class, $message); // a form with one "name" field
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    /** @var string $plainToken */
    $plainToken = $this->handle($message);
}
```

`CreateMcpToken` carries the validation constraints for `name` (`NotBlank`, max 255 characters). Don't route it to
an async transport, the controller needs the return value.

### Listing tokens

`McpTokenRepository::findForUser($user)` returns the tokens of one user. `findAllWithUser()` returns every token
with its user loaded, for an admin overview. Guard that overview with `ROLE_ADMIN` yourself, for example with an
`access_control` rule.

### Revoking a token

Check `McpTokenVoter::REVOKE` before dispatching `RevokeMcpToken`. The voter grants a user their own tokens and grants
`ROLE_ADMIN` every token. The handler does no authorization of its own.

```php
use SumoCoders\FrameworkCoreBundle\Mcp\Message\RevokeMcpToken;
use SumoCoders\FrameworkCoreBundle\Mcp\Security\McpTokenVoter;

$this->denyAccessUnlessGranted(McpTokenVoter::REVOKE, $token);
$this->messageBus->dispatch(new RevokeMcpToken((int) $token->getId()));
```

Use a form for the revoke button, so the request carries a CSRF token.

## Troubleshooting

- **`Could not find the entity manager for class "SumoCoders\FrameworkCoreBundle\Mcp\Entity\McpToken"`**:
  `mcp_tokens.user_class` is not set, while some code still uses `McpTokenRepository`, the authenticator or a
  handler.
- **`... is not a class implementing UserInterface`** at container compile: `user_class` points to a class that does
  not exist or does not implement `UserInterface`.
- **Every MCP request returns 401**: check that the client sends `Authorization: Bearer <token>`, and that the `mcp`
  firewall sits above `main` so its pattern matches first.
- **A token from before the switch to this bundle still works**: on purpose. The table stores a SHA-256 hash of the
  full token, the prefix plays no role in the lookup.

## Internals

### Control flow

- `SumoCodersFrameworkCoreExtension::prepend()` reads `mcp_tokens.user_class`. When it is set, it prepends two things
  to the Doctrine config: a `resolve_target_entities` entry `UserInterface` → `user_class`, and a separate mapping
  `FrameworkCoreBundleMcp` for `src/Mcp/Entity`. `load()` sets the
  `sumo_coders_framework_core.mcp_tokens.token_prefix` parameter for `CreateMcpTokenHandler`.
- `config/services.php` always registers the repository, the authenticator, the voter and both handlers. Without
  `user_class` they stay registered but unusable, like the rest of the bundle's services the graph doesn't change
  with config (see `docs/adr/0006-mcp-tokens-opt-in-doctrine-mapping.md`).
- `McpTokenAuthenticator::authenticate()` hashes the bearer token, looks it up, sets `lastUsedAt` and flushes, then
  returns a `SelfValidatingPassport` whose user loader returns the token's user directly. The user provider of the
  firewall does not reload the user.
- `McpTokenVoter` compares owners on `getUserIdentifier()`, then falls back to
  `AccessDecisionManagerInterface::decide($token, ['ROLE_ADMIN'])`, so the role hierarchy applies.

### Gotchas

- `McpToken` lives in `src/Mcp/Entity/`, not `src/Entity/`. The bundle's base mapping scans `src/Entity/`
  recursively, so an `McpToken` in there would get mapped in every application, also without a `user_class`. Doctrine
  would then fail on the unresolved `UserInterface` relation in the schema tool and the proxy cache warmer.
- The table name `mcp_api_token`, its columns and its index names match the table Automator created before this
  moved into the bundle, so Automator can switch without a schema change.
- `lastUsedAt` gets a flush on every authenticated MCP request.

### Known limitations

- No expiry date. A token stays valid until someone revokes it or deletes the user (the foreign key cascades).
- No controllers, templates or translations: each application builds its own UI.
