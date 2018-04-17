# Language switch

You can create a language switch using this code

```
{% if app.request.attributes.has('_route_params') %}
  <ul class="language-select nav navbar-nav">
    {% for locale in locales %}
     <li {% if locale == app.request.locale %}class="active"{% endif %}>
       <a href="{{ path(app.request.attributes.get("_route"), app.request.attributes.get('_route_params')|merge({"_locale": locale})) }}">
         {{ locale }}
       </a>
     </li>
    {% endfor %}
  </ul>
{% endif %}
```
