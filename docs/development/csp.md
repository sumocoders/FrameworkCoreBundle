# Content security policy

The bundle sets pretty strict CSP headers on every response out-of-the-box. This prevents a large portion of XSS attacks
on applications built with the framework.

For more information, read https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP

## Base rules

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
on which the resources are hosted.

You can either tweak the CSP header inside a specific controller (where you already have a Response object), or add an
event listener on the kernel response event and tweak the headers there (globally).

Note that you need to include the existing CSP headers.

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
    public function onKernelResponse(ResponseEvent $event): void
    {
        // allow Google Maps to be loaded
        $headers = [
            'default-src \'self\'',
            'style-src \'self\' https://fonts.googleapis.com \'unsafe-inline\'',
            'font-src \'self\' https://fonts.gstatic.com',
            'frame-src \'none\'',
            'script-src \'self\' \'nonce-FOR725\' maps.googleapis.com',
            'img-src \'self\' data: maps.gstatic.com *.googleapis.com *.ggpht.com maps.google.com',
        ];

        $event->getResponse()->headers->set(
            'Content-Security-Policy',
            implode('; ', $headers)
        );
    }
}

```
