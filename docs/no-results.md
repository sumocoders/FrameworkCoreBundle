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

## After filtering

When the list is empty because of an active filter, say so in its own message ("no items match these filters"),
not the message for an empty list. The filled-in filter fields already show what was searched for, so the empty
state gets no reset button:

```twig
<div class="data-no-results">
    <img src="{{ asset('images/no-results.svg') }}" alt="">
    {{ (filtersActive ? 'items.no_matches' : 'items.empty')|trans }}
</div>
```

A filter with more than four inputs gets a reset button next to its filter button instead; see the overview page in
[card-layouts.md](card-layouts.md#overview-page).

## Inside a section

When the empty list is one section of a larger page, such as the notes or contacts on a detail page, the
140px illustration takes more room than the section would with content in it. Use one muted line instead:

```twig
<div class="card-body">
    <p class="text-body-secondary mb-0">{{ 'your.translation.key'|trans }}</p>
</div>
```

## Inside a table

A table without rows is left out, and the muted line takes its place. An empty `<thead>` above a message that
sits in a spanning cell reads as a broken table:

```twig
<div class="card-body">
    {% if tasks is not empty %}
        <div class="table-responsive">
            <table class="table">
                {# ... #}
            </table>
        </div>
    {% else %}
        <p class="text-body-secondary mb-0">{{ 'your.translation.key'|trans }}</p>
    {% endif %}
</div>
```

When the table is the page's main list, render the full no-results component instead of the table.

## Accessibility

The `<img>` carries an empty `alt=""` because it is decorative. The text content must be meaningful on its own.
