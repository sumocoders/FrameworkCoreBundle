[Back to index](index.md)

# Mail

Provides one shared base template that any consuming-project email template extends from, so mail markup gets
consistent table-based layout and inlined CSS across mail clients without each project reimplementing it. Sending
itself is plain `symfony/mailer` (`TemplatedEmail`) routed through the async Messenger transport — this bundle does
not wrap the mailer API, only the template.

Primary code reference: `templates/Mail/base.html.twig`

## How the base template works

```twig
{% apply inky_to_html|inline_css(asset_content('styles/mail.scss')) %}
  ...
  {% block content %}{{ content|raw }}{% endblock %}
  ...
{% endapply %}
```

The whole body is wrapped in one `{% apply %}` chain:

1. `inky_to_html` (from `twig/inky-extra`) expands Foundation-for-Emails Inky tags (`<container>`, `<row>`,
   `<columns>`, `<spacer>`, `<center>`) into real, table-based HTML that survives mail-client HTML stripping.
2. `inline_css` (from `twig/cssinliner-extra`) then walks the resulting HTML and inlines the given CSS directly onto
   each element as a `style` attribute — most mail clients ignore `<style>` blocks and `<link>` tags entirely, so
   inlining is mandatory, not cosmetic.
3. The CSS passed to `inline_css` comes from `asset_content('styles/mail.scss')` — the bundle's own
   `asset_content()` Twig function (`src/Twig/AssetContentExtension.php`, see
   [twig-extensions.md](twig-extensions.md)), which resolves the *compiled* AssetMapper output for that asset and
   returns it as a string. This is why `asset_content()` exists as a separate extension from the generic
   `content()`: it must resolve through AssetMapper, not read `public/` directly, because `mail.scss` is compiled/
   versioned by AssetMapper.

Neither `inky_to_html` nor `inline_css` nor `AssetContentExtension` is mail-specific by itself — this template is
simply where they're composed together for that purpose.

Consuming templates extend the base and only fill `{% block content %}`; `{% block logo %}` is also overridable.
The logo `<img>` uses a hardcoded pixel `width` attribute (not CSS) because many mail clients don't apply CSS-based
image sizing.

## Async dispatch

Mail is sent with the standard Symfony `TemplatedEmail` + `MailerInterface::send()` — no bundle-specific service.
Symfony's mailer is wired to dispatch through the `async` Messenger transport, so `send()` only enqueues the message;
actual sending happens when `messenger:consume async` processes it (expected to run continuously, e.g. via crontab in
consuming projects). Practical consequence for anyone building a mail: everything in the `TemplatedEmail` context
must be serializable (Messenger serializes the message for the transport), so pass scalars/arrays/ids, not live
entities or services.

## Where it is used

Any consuming-project mail template that does `{% extends '@SumoCodersFrameworkCore/Mail/base.html.twig' %}`. See
[mails.md](../mails.md) for the user-facing usage guide and full example.

## Edge cases

- `asset_content()` throws `AssetNotFoundException` if `styles/mail.scss` isn't resolvable by AssetMapper in the
  consuming project — the mail asset must be part of that project's asset mapper paths.
- Changing the logo's rendered width requires updating the `width` attribute on the `<img>` tag to match; CSS-only
  resizing is unreliable across mail clients.
