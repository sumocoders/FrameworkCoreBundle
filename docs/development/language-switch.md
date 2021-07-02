# Language switch

You can add a language switch to navigation.html.twig by using the following snippet:

```
<div class="navbar-header d-md-none d-flex align-items-center">
  <div class="dropdown btn-group d-flex flex-column mr-3">
    <a class="dropdown-toggle d-flex align-items-center" href="#" id="dropdown-language" role="button"
      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
      {{ app.request.locale|upper }}
      <span class="fas fa-chevron-down fa-w-16"></span>
    </a>
    <ul class="dropdown-menu dropdown-menu-right" aria-labelledby="dropdown-language">
      {% for locale in locales|split('|') %}
        <li>
          <a class="dropdown-item {{ locale == app.request.locale ? 'active' : '' }}" href="{{ path(app.request.attributes.get('_route'), app.request.attributes.get('_route_params')|merge({"_locale": locale})) }}">{{ locale != '' ? locale|upper : 'NL' }}</a>
        </li>
      {% endfor %}
    </ul>
  </div>
  <button class="navbar-toggler navbar-toggler-right ml-auto collapsed" type="button" data-toggle="collapse" data-target="#navbar-collapse-1" aria-controls="navbar-collapse-1" aria-expanded="false" aria-label="Toggle navigation">
    <span class="navbar-toggler-icon"></span>
  </button>
</div>

```
Place it between the logo and user menu dropdown.
