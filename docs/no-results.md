# No-results state

Use this component when a list, datagrid, or page has no data to display, including after filtering produces zero
results.

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

## Accessibility

The `<img>` carries an empty `alt=""` because it is decorative. The text content must be meaningful on its own.
