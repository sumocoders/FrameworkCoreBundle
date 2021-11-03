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

## Extending the headers

In some cases, you might have to allow external CSS and/or JS in your project. To do so, you'll have to allow the domain
on which the resource is hosted.

You can either tweak the CSP header inside a specific controller (where you already have a Response object), or add an
event listener on the kernel response event and tweak the headers there (globally).

services.yaml

```yaml
  App\EventListener\ResponseListener:
    tags:
      - { name: kernel.event_listener, event: kernel.response, method: onKernelResponse, priority: -5 }
```

ResponseListener.php

```php
<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseListener
{
    public function onKernelResponse(ResponseEvent $event)
    {
        $event->getResponse()->headers->set('Content-Security-Policy',
            "script-src https://your-cdn.com/your-script.js",
            false // Passing false here will add the new headers instead of overwrite
        );
    }
}

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
