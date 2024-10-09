# No results in datagrid / no data on page

Use the following 'no results' snippet when there are no results or data in a (filtered) datagrid or on a page.

```
<div class="data-no-results">
    <img src="{{ asset('images/no-results.svg') }}" alt="">
    {{ 'quotation.empty.items'|trans }} (replace with correct translation)
</div>
```
