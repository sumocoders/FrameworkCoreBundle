# Button locations

## Toolbar below header

Buttons in the toolbar below the header are used for secondary actions that users infrequently need to access.
Put them in {% block header_navigation %}

```
{% block header_navigation %}
    
{% endblock %}
```

## Fixed toolbar on the bottom
Buttons in the fixed toolbar on the bottom are used for primary actions that users frequently need to access.
They are always visible, even when the user scrolls down the page.

There are three positions:
- **Left** `{% block header_actions_left %}` -- typically used for "dangerous" actions like delete
- **Center** `{% block header_actions_center %}` -- other actions that are frequently used but not primary
- **Right** `{% block header_actions_right %}` -- typically used for primary actions like save or add

```
{% block header_actions_left %}
  
{% endblock %}

{% block header_actions_center %}

{% endblock %}

{% block header_actions_right %}
    
{% endblock %}
```

## Submit forms with buttons in the fixed toolbar

When using a form, you can place the submit button in the fixed toolbar by adding the `form` attribute to the button.

```twig
{% block header_actions_right %}
    <button class="btn btn-primary" type="submit" form="{{ form.vars.id }}" data-turbo="true">
        <i class="bi bi-floppy-fill mr-2"></i>
        {{ 'Save'|trans }}
    </button>
{% endblock %}
```

The important part is `form="{{ form.vars.id }}"` which links the button to the form.
