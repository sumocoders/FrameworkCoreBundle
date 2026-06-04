# Language switch

The bundle supports multi-locale navigation. The active locale is part of the route URL via the `{_locale}` parameter. A dropdown in the navigation lets users switch language.

## Prerequisites

Routing must be configured with `{_locale}` as a route parameter or prefix in `config/routes.yaml`.

## Adding a new locale

Extend the `locales` parameter in `config/services.yaml`:

```yaml
parameters:
    locales: ['nl', 'fr', 'en']
```

The `locales` parameter is passed to Twig and used in the language switcher dropdown.

## Language switch snippet

Add the following to `templates/navigation.html.twig`, between the logo and the user menu:

```twig
<div class="navbar-header d-md-none d-flex align-items-center">
    <div class="dropdown btn-group d-flex flex-column me-3">
        <a class="dropdown-toggle d-flex align-items-center"
           href="#"
           id="dropdown-language"
           role="button"
           data-bs-toggle="dropdown"
           aria-haspopup="true"
           aria-expanded="false">
            {{ app.request.locale|upper }}
            <span class="bi bi-chevron-down"></span>
        </a>
        <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
            {% for locale in locales %}
                <li>
                    <a class="dropdown-item {{ locale == app.request.locale ? 'active' : '' }}"
                       href="{{ path(app.request.attributes.get('_route'), app.request.attributes.get('_route_params')|merge({'_locale': locale})) }}">
                        {{ locale|upper }}
                    </a>
                </li>
            {% endfor %}
        </ul>
    </div>
</div>
```

## Troubleshooting

- **Locale not changing on click** — verify your routes include `{_locale}` as a parameter or prefix; without it, `_locale` in `_route_params` has no effect
- **`locales` variable undefined in Twig** — ensure the `locales` parameter is defined in `config/services.yaml` under `parameters:`
- **Wrong locale active after switch** — check that the Symfony locale listener is active (`framework.translator.enabled_locales` in `config/packages/translation.yaml`)
