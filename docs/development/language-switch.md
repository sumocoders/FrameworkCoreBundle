# Language switch

You can create a language switch using this code

```
{% if app.request.attributes.has('_route_params') %}
  <ul class="language-select nav navbar-nav">
    {% for locale in locales %}
      <li {% if locale == app.request.locale %}class="active"{% endif %}>
        {% set url = path(app.request.attributes.get("_route"), app.request.attributes.get('_route_params')|merge({"_locale": locale})) %}
        {% if app.request.query.all|length > 0 %}
          {% set url = url ~ '&' ~ app.request.query.all|url_encode %}
        {% endif %}
        <a href="{{ url }}">{{ locale|upper }}</a>
      </li>                
   {% endfor %}              
  </ul>
{% endif %}
```
