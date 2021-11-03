# Content security policy

The bundle sets pretty strict CSP headers on every response out-of-the-box. This prevents a large portion of XSS attacks
on applications built with the framework.

For more information,
read [Content-Security-Policy](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy).

## Default Content Security Policies

The default Content Security Policies are:

```php
// The default rule and fallback: only allow content from our own domain
"default-src 'self';" .
// CSS: only allow self hosted CSS and make an exception for Google Fonts
"style-src 'self' https://fonts.googleapis.com;" .
// Fonts: only allow self hosted fonts and make an exception for Google Fonts
"font-src 'self' https://fonts.gstatic.com;" . 
// iframes: block all
"frame-src 'none';" . 
// JS: only allow self hpsted JS and specifically allow our inline jsData script with a nonce
"script-src 'self' 'nonce-FOR725'"
```


## Extending the default Content Security Policies

In some cases, you might have to allow external CSS and/or JS in your project. To do so, you'll have to allow the domain
on which the resource is hosted.

You can either tweak the CSP header inside a specific controller (where you already have a Response object). Or you can 
add extra policies application wide by using a configuration file: `config/packages/sumo_coders_framework_core.yaml`.

In this file you can set the extra policies. Hereunder you can find an example to allow Google Maps.

```yaml
sumo_coders_framework_core:
  extra_content_security_policy:
    script-src:
      - 'maps.googleapis.com'
    img-src:
      - "'self'"
      - 'data: maps.gstatic.com'
      - '*.googleapis.com'
      - '*.ggpht.com'

```


## Overriding the default Content Security Policies

The default Content Security Policies are initialized through configuration under the
key: `sumo_coders_framework_core.content_security_policy`. So you can overrule this by creating a
file: `config/packages/sumo_coders_framework_core.yaml`.

In this file you can set the directives you want like below:

```yaml
sumo_coders_framework_core:
  content_security_policy:
    default-src:
      # Default rule: only allow content from our own domain
      - "'self'"
    style-src:
      - "'self'"
      # Allow Google Fonts
      - 'https://fonts.googleapis.com'
    font-src:
      - "'self'"
      # Allow Google Fonts
      - 'https://fonts.gstatic.com'
    frame-src:
      # Block all iframes
      - "'none'"
    script-src:
      - "'self'"
      # Allow our jsData inline script
      - "'nonce-FOR725'"

```

# X-Frame-Options
See [X-Frame-Options](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-Frame-Options) for more information.

Can be configured by setting `sumo_coders_framework_core.x-frame-options`. The default is `deny`. If you set an empty
string the header won't be added.
