# No-results state

Use this component when the main list or datagrid of a page has no data to display, including after filtering
produces zero results. For an empty section inside a larger page, see [Inside a section](#inside-a-section).

## Usage

```twig
<div class="data-no-results">
    <img src="{{ asset('images/no-results.svg') }}" alt="">
    {{ 'your.translation.key'|trans }}
</div>
```

Replace `'your.translation.key'` with a translation key appropriate to the context (e.g. `'users.empty'`,
`'orders.no_results'`).

## With a filter hint

When the empty state is caused by an active filter, add a reset link so the user can clear it:

```twig
{% if filtersActive %}
    <div class="data-no-results">
        <img src="{{ asset('images/no-results.svg') }}" alt="">
        {{ 'your.translation.key'|trans }}
        <a href="{{ path(app.request.attributes.get('_route')) }}">{{ 'general.reset_filters'|trans }}</a>
    </div>
{% endif %}
```

## Inside a section

When the empty list is one section of a larger page, such as the notes or contacts on a detail page, the
140px illustration takes more room than the section would with content in it. Use one muted line instead:

```twig
<div class="card-body">
    <p class="text-body-secondary mb-0">{{ 'your.translation.key'|trans }}</p>
</div>
```

## Accessibility

The `<img>` carries an empty `alt=""` because it is decorative. The text content must be meaningful on its own.
