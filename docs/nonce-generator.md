# CSP nonce generator

Lets an incoming request supply its own Content-Security-Policy nonce (via an `X-CSP-Nonce` header), instead of
always minting a fresh random one. This is useful when an upstream layer (reverse proxy, SSR shell) needs to render
an inline `<script nonce="...">` that must match the nonce Symfony's CSP header ends up using.

## Prerequisites

- [`nelmio/security-bundle`](https://github.com/nelmio/NelmioSecurityBundle) must be installed and configured to use
  CSP nonces. `NonceGenerator` decorates that bundle's `nelmio_security.nonce_generator` service — with
  `nelmio/security-bundle` not installed, this decorator has nothing to decorate.
- No action needed beyond having `nelmio/security-bundle` installed and this bundle registered — the decorator is
  wired automatically in `config/services.php` and requires no configuration.

## Usage

Nothing to call directly. Once `nelmio/security-bundle` is configured for CSP nonces, this decorator is consumed
transparently wherever a nonce is needed: NelmioSecurityBundle's CSP header and the `csp_nonce()` Twig function both
end up using the same value.

To make an upstream layer's inline script share Symfony's nonce, set the `X-CSP-Nonce` header on the incoming
request before it reaches Symfony:

```
X-CSP-Nonce: <value the reverse proxy also used in its own inline <script nonce="...">>
```

Symfony's CSP header and `csp_nonce()` will then both return that same value for the rest of the request.

## Behavior

- `kernel.request` (priority `400`, deliberately high — must run before NelmioSecurityBundle builds its CSP header):
  reads the `X-CSP-Nonce` request header, if present, and caches it for the current request.
- `generate()` (from `NonceGeneratorInterface`) returns that cached value if one was captured, otherwise delegates to
  the original `nelmio_security.nonce_generator` — NelmioSecurityBundle's normal random-nonce behavior.
- `kernel.response`: resets the cached value back to `null`, so a nonce from one request can't leak into another
  request handled by the same PHP worker/process.

## Security

The `X-CSP-Nonce` header value is trusted as-is, with no validation, before being echoed into the CSP header and any
nonce attribute rendered in the page. Only ever accept this header from a trusted layer — set (and stripped) at your
reverse proxy — never pass through a value read directly from an untrusted client.

## Troubleshooting

- **Nonce in the page doesn't match the CSP header**: something downstream of Symfony is rewriting the CSP header or
  the inline `nonce` attribute after the response leaves this bundle; verify no other middleware regenerates either
  value.
- **`X-CSP-Nonce` seems to be ignored**: confirm `nelmio/security-bundle` is installed and actually configured for
  CSP nonces — without it, nothing calls `generate()` and this decorator has no effect.
- **A client-supplied nonce made it into the response**: the header is not being stripped/overwritten at the reverse
  proxy boundary — see [Security](#security) above.
