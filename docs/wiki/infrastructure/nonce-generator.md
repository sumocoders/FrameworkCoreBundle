[Back to index](index.md)

# CSP nonce generator

Lets an incoming request supply its own Content-Security-Policy nonce (via an `X-CSP-Nonce` header) instead of
NelmioSecurityBundle always minting a fresh random one — useful when an upstream layer (reverse proxy, SSR shell)
needs to inject an inline `<script nonce="...">` that must match the nonce Symfony puts in the CSP header.

Code: `src/Service/Security/NonceGenerator.php`

## Workflow

- Registered as a service decorator: `config/services.php` decorates `nelmio_security.nonce_generator` and injects
  the original as `.inner` (constructor-promoted as `$parent`).
- `generate()` (from `NonceGeneratorInterface`) returns the cached per-request `$requestNonce` if one was captured,
  otherwise delegates to `$parent->generate()` — NelmioSecurityBundle's normal random-nonce behavior.
- Also implements `EventSubscriberInterface` directly (not via `#[AsEventListener]`) to hook the request/response
  cycle:
  - `KernelEvents::REQUEST` (priority `400`, deliberately high — must run before NelmioSecurityBundle builds its CSP
    header): reads the `X-CSP-Nonce` request header into `$requestNonce` if present.
  - `KernelEvents::RESPONSE`: resets `$requestNonce` back to `null`, so a nonce from one request can't leak into
    another request handled by the same PHP worker/process.

## Where it is used

- Consumed transparently by NelmioSecurityBundle wherever it needs a nonce value for the CSP header and for the
  `csp_nonce()` Twig function — this class doesn't need to be called directly.
- Only takes effect if `nelmio/security-bundle` is installed and configured to use CSP nonces; otherwise this
  decorator has nothing to decorate.

## Edge cases

- The `X-CSP-Nonce` header value is trusted as-is with no validation before being echoed into the CSP header and any
  nonce attribute rendered in the page — only accept this header from a trusted layer (e.g. strip/set it at a
  reverse proxy), never directly from untrusted clients.
