[Back to index](index.md)

# Doctrine extension listener (Gedmo Blameable/Loggable wiring)

Intended to feed the current authenticated user into Gedmo's `BlameableListener` and `LoggableListener` on every
request, so entities using Gedmo's `Blameable`/`Loggable` behaviors can record who created/changed a row.

Code: `src/EventListener/DoctrineExtensionListener.php`

## Workflow (as written)

- `onKernelRequest(RequestEvent $event)`: if a security token exists and the user is at least
  `IS_AUTHENTICATED_REMEMBERED`, fetches the user from the token and calls `LoggableListener::setUsername($user)`
  and `BlameableListener::setUserValue($user)`.

## Gotcha: this listener is not wired up

This class is **not registered in `config/services.php`**, carries no `#[AsEventListener]` attribute, and does not
implement `EventSubscriberInterface` — so autoconfiguration wouldn't pick it up as a listener even if it were a
public service. A repo-wide grep confirms no other file references `DoctrineExtensionListener` at all.

As shipped in this bundle, the class is dead code: nothing instantiates it or calls `onKernelRequest()`. To actually
get Blameable/Loggable to pick up the current user, a consuming application must itself:

1. Register `DoctrineExtensionListener` as a service (its constructor dependencies —
   `TokenStorageInterface`, `AuthorizationCheckerInterface`, `Gedmo\Loggable\LoggableListener`,
   `Gedmo\Blameable\BlameableListener` — are all normal, autowireable services), and
2. Tag it `kernel.event_listener` for the `kernel.request` event, method `onKernelRequest`.

`gedmo/doctrine-extensions` is a `composer.json` dependency of this bundle, so the listener classes it depends on
are available — but installing the bundle alone does not enable Blameable/Loggable tracking.

## Where it is used

Nowhere in this repository currently. No doc file, config, or other code path enables this listener.
