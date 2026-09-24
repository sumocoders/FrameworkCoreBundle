# Card layouts

Every page built on `base.html.twig` puts its content in cards (see "Page composition" in
[../DESIGN.md](../DESIGN.md)). This page shows the layouts that come up in almost every project: section
cards, a form split into sections, an overview page with a filter and a grid of item cards, and the empty
state that goes with it.

The examples use a placeholder entity called `item`. Replace the routes, fields and translation keys with your
own. Keys under `datagrids.actions.*` ship with the bundle; the others are project keys you add yourself.

## Section cards

One card per section, stacked with `mb-3`. A section with a title puts it in `.card-header`, not as a heading
inside the body.

```twig
{% block main %}
    <div class="card mb-3">
        <div class="card-header">
            {{ 'item.detail.information'|trans }}
        </div>
        <div class="card-body">
            <dl class="mb-0">
                <dt>{{ 'item.name'|trans }}</dt>
                <dd>{{ item.name }}</dd>
            </dl>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header">
            {{ 'item.detail.history'|trans }}
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    {# ... #}
                </table>
            </div>
        </div>
    </div>
{% endblock %}
```

- A form page is a card too: `<div class="card mb-3"><div class="card-body">{{ form(form) }}</div></div>`.
- A form with several sections keeps one `form_start()` / `form_end()` around all of its cards, so the submit
  button in the fixed toolbar (see [button-locations.md](button-locations.md)) still submits everything. See
  [Form layout](#form-layout).
- `.table-responsive` goes on a wrapper `<div>`, never on the `<table>` itself. On the table it does nothing.

## Form layout

A form with more than a handful of fields gets one titled card per group of related fields. When the create
and update pages show the same form, the layout lives in one partial that both include.

```twig
{# item/_form.html.twig #}
{{ form_start(form) }}

<div class="card mb-3">
    <div class="card-header">
        {{ 'item.form.section.general'|trans }}
    </div>
    <div class="card-body">
        {# Fields the rest of the form depends on come first. #}
        <div class="row">
            <div class="col-md-4 col-lg-3">
                {{ form_row(form.country) }}
            </div>
            <div class="col-md-8 col-lg-9">
                <div class="form-group">
                    {{ form_label(form.reference) }}
                    <div class="input-group">
                        {{ form_widget(form.reference) }}
                        {# Merge: passing attr on its own drops the Stimulus attributes set in the form type. #}
                        {{ form_widget(form.lookup, {attr: form.lookup.vars.attr|merge({class: 'btn-outline-primary'})}) }}
                    </div>
                    {{ form_errors(form.reference) }}
                    <div class="form-text">{{ 'item.form.lookup_help'|trans }}</div>
                </div>
            </div>
        </div>
        {{ form_row(form.name) }}
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        {{ 'item.form.section.contact'|trans }}
    </div>
    <div class="card-body">
        {{ form_row(form.address) }}
        {# Collection buttons stick out 20px on both sides: .gx-5 keeps them apart. #}
        <div class="row gx-5">
            <div class="col-md-6">
                {{ form_row(form.phones) }}
            </div>
            <div class="col-md-6">
                {{ form_row(form.emails) }}
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header">
        {{ 'item.remarks'|trans }}
    </div>
    <div class="card-body">
        {# The card title already names the only field in it. #}
        {{ form_row(form.remarks, {label: false, attr: {'aria-label': 'item.remarks'|trans}}) }}
    </div>
</div>

{{ form_end(form) }}
```

```twig
{# item/create.html.twig and item/update.html.twig #}
{% block main %}
    {{ include('item/_form.html.twig') }}
{% endblock %}
```

- Order fields by task flow. Here the country and reference feed a lookup that fills in the name, so they come
  before it.
- Short, related fields share a row; long text and textareas take the full width.
- An action on a single field (look up, generate, copy) sits in an `.input-group` with that field. The
  `.form-text` explains what it does, and `form_errors()` stays below the input group.

## Overview page

An overview is built from four parts, top to bottom:

1. A filter card, when the list can be filtered.
2. A result summary: the number of results, plus a reset link while a filter is active.
3. The results: a grid with one card per item, or one card holding a table.
4. Pagination.

```twig
{% block main %}
    {% set is_filtered = form.vars.submitted and form.vars.valid %}

    <div class="card mb-3">
        <div class="card-body">
            {{ form_start(form) }}
            <div class="row gy-2 align-items-center">
                <div class="col-md-5 col-lg-4">
                    {{ form_widget(form.term, {attr: {
                        placeholder: 'item.filter.term'|trans|ucfirst,
                        'aria-label': 'item.filter.term'|trans|ucfirst,
                    }}) }}
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i>
                        {{ 'item.filter.submit'|trans }}
                    </button>
                </div>
                {# Secondary actions (export, ...) sit apart from the primary one, pushed right. #}
                <div class="col-auto ms-md-auto">
                    <button type="submit" class="btn btn-outline-secondary" formaction="{{ path('item_export') }}" data-turbo="false">
                        <i class="bi bi-file-earmark-arrow-down"></i>
                        {{ 'item.filter.export'|trans }}
                    </button>
                </div>
            </div>
            {{ form_end(form) }}
        </div>
    </div>

    {% if items.numResults > 0 %}
        <p class="text-body-secondary mb-3">
            {{ 'item.overview.count'|trans({count: items.numResults}) }}
            {% if is_filtered %}
                &middot;
                <a href="{{ path(app.request.attributes.get('_route')) }}">{{ 'general.reset_filters'|trans }}</a>
            {% endif %}
        </p>
    {% endif %}

    <div class="row g-3 mb-3">
        {% for item in items %}
            <div class="col-md-6 col-lg-4">
                {{ include('item/_card.html.twig', {item: item}) }}
            </div>
        {% else %}
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="data-no-results">
                            <img src="{{ asset('images/no-results.svg') }}" alt="">
                            {% if is_filtered %}
                                {{ 'item.overview.no_matches'|trans }}
                                <a href="{{ path(app.request.attributes.get('_route')) }}">{{ 'general.reset_filters'|trans }}</a>
                            {% else %}
                                {{ 'item.overview.empty'|trans }}
                            {% endif %}
                        </div>
                    </div>
                </div>
            </div>
        {% endfor %}
    </div>

    <div class="d-flex justify-content-center">
        {{ pagination(items) }}
    </div>
{% endblock %}
```

`items` is a [Paginator](pagination.md), which provides `numResults`. The count key uses ICU plurals, so it needs
the `+intl-icu` translation domain:

```yaml
# translations/messages+intl-icu.nl.yaml
item.overview.count: '{count, plural, one {# item} other {# items}}'
```

Notes on the filter card:

- A widget rendered with `form_widget()` has no visible label. Give it a `placeholder` and an `aria-label`.
- One primary button per filter card. Everything else is `btn-outline-secondary`.
- "No data yet" and "no matches for this filter" are different situations and get different messages. Only the
  second one gets a reset link. See [no-results.md](no-results.md).

## Item card

An item card has three regions. Use the ones the item needs and leave the others out.

| Region                   | Contains                                                                  |
|--------------------------|---------------------------------------------------------------------------|
| `.card-img-top`          | An optional image or logo                                                 |
| `.card-body`             | The title, status badges and the item's key fields                        |
| `.card-footer`           | Actions on this item. Leave the footer out when the item has no actions   |

```twig
{# item/_card.html.twig #}
<div class="card">
    {% if item.image %}
        <img src="{{ item.image.webPath }}" class="card-img-top object-fit-contain p-3" height="128" alt="">
    {% endif %}
    <div class="card-body">
        <div class="d-flex align-items-start justify-content-between gap-2">
            <h2 class="h5 card-title mb-0">
                <a class="link-body-emphasis" href="{{ path('item_detail', {item: item.id}) }}">{{ item.name }}</a>
            </h2>
            {% if item.archived %}
                <span class="badge text-bg-secondary">{{ 'item.archived'|trans }}</span>
            {% endif %}
        </div>
        {% if item.email or item.phone %}
            <ul class="list-unstyled mt-3 mb-0">
                {% if item.email %}
                    <li class="text-truncate">
                        <i class="bi bi-envelope me-2" title="{{ 'item.email'|trans }}"></i>
                        <a href="mailto:{{ item.email }}">{{ item.email }}</a>
                    </li>
                {% endif %}
                {% if item.phone %}
                    <li class="text-truncate">
                        <i class="bi bi-telephone me-2" title="{{ 'item.phone'|trans }}"></i>
                        <a href="tel:{{ item.phone }}">{{ item.phone }}</a>
                    </li>
                {% endif %}
            </ul>
        {% endif %}
    </div>
    <div class="card-footer d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm px-3"
           href="{{ path('item_update', {item: item.id}) }}"
           title="{{ 'datagrids.actions.edit'|trans|ucfirst }}"
           data-controller="tooltip" data-bs-placement="top"
        >
            <i class="bi bi-pencil-fill"></i>
            <span class="visually-hidden">{{ 'datagrids.actions.edit'|trans|ucfirst }}</span>
        </a>
    </div>
</div>
```

The title:

- The page `<h1>` is in the header bar, so card titles are `<h2>`, sized down with `.h5`.
- When the item has a detail page, the title links to it. That link is the main way into the item, so a
  separate "view" button is optional.
- Status badges (archived, draft, overdue, ...) sit next to the title, not on a line of their own.

The fields:

- Show a field only when it has a value. A label followed by nothing ("Email:") is noise.
- A short list with a Bootstrap Icon per field reads faster than "Label: value" rows. The icon carries a `title`
  so the meaning is still available.
- Long values (e-mail addresses, URLs) get `.text-truncate` so they cannot push the card wider than its column.
- Collections from imported or legacy data can hold empty strings. Filter them before rendering
  (`item.emails|filter(email => email is not empty)`), or the list shows an icon with no value next to it.

The actions:

- Which actions an item has depends on the entity: edit, view, download, duplicate, open in an external tool, or
  none at all. The layout stays the same: a `.card-footer.d-flex.gap-2` with `btn-sm` buttons.
- An icon-only button needs an accessible name. Put `title` and `data-controller="tooltip"` on the `<a>` or
  `<button>` itself, not on the `<i>`, and add the label again as `.visually-hidden` text.
- A button with a visible label needs neither.
- Prefer keeping destructive actions (delete) out of the card footer, next to harmless actions. The detail or
  edit page has a place for them in `header_actions_left`, behind a confirmation (see
  [button-locations.md](button-locations.md)).

## Cards with little content

Cards in the same `.row` stretch to the tallest card (`.card` has `height: 100%`). When every card in a row has
little content, the whole row collapses to the height of a title. Give the card body a minimum height in a
project class, sized for a title plus the fields a typical item shows:

```scss
// assets/styles/style.scss
.item-card .card-body {
  min-height: 7rem;
}
```

```twig
<div class="card item-card">
```

## Cards with dropdowns

`.card` has `overflow: hidden`, which clips anything positioned outside it. The list of an autocomplete field
(Tom Select), a `.dropdown-menu` or a popover inside a card gets cut off at the card edge. Add
`.overflow-visible` to every card that contains one:

```twig
<div class="card mb-3 overflow-visible">
    <div class="card-body">
        {{ form_row(form.company) }} {# an autocomplete field #}
    </div>
</div>
```

Add it per card, only where a field needs it, and keep the bundle default everywhere else.

## Accessibility

- Keep the heading order intact: `<h1>` in the header bar, `<h2>` for card titles, `<h3>` inside a card.
- Every icon-only control has an accessible name (`.visually-hidden` text or `aria-label`).
- The empty-state image is decorative (`alt=""`); the message next to it carries the meaning.
