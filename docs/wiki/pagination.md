[Back to index](index.md)

# Pagination

Slices a Doctrine query into pages and exposes the counts/bounds a pager widget needs, so repositories/controllers
don't hand-roll `setFirstResult`/`setMaxResults` math; implemented as a thin wrapper around Doctrine ORM's own
`Tools\Pagination\Paginator`.

`src/Pagination/Paginator.php`

## How it wraps the `QueryBuilder`

`paginate(int $page = 1)` mutates the **injected** `QueryBuilder` in place (`setFirstResult`/`setMaxResults` on
`$this->queryBuilder`, not a clone) and wraps its `Query` in Doctrine's `Tools\Pagination\Paginator` with
`$fetchJoinCollection` hardcoded to `true`. Two correctness/perf knobs are set automatically rather than exposed as
options:

- **No joins present** (`count($queryBuilder->getDQLPart('join')) === 0`) → explicitly disables the count query's
  `DISTINCT` (`$query->setHint(CountWalker::HINT_DISTINCT, false)`), since without joins duplicate root rows can't
  occur and distinct counting only costs performance.
- **`HAVING` clause present** (`getDQLPart('having') !== null`) → `setUseOutputWalkers(true)` on the inner Doctrine
  paginator. Output walkers are otherwise left off (faster), since they're only required for `GROUP BY`/`HAVING`
  correctness.

`$this->results` / `$this->numResults` are populated once, at `paginate()` time — calling `paginate()` again re-runs
both queries; nothing here is cached or memoized across calls.

## What it exposes

| Concern | Methods |
|---|---|
| Current position | `getCurrentPage()`, `getPageSize()` (default `self::PAGE_SIZE = 30`, override via constructor) |
| Bounds | `getLastPage()`, `hasPreviousPage()`/`getPreviousPage()`, `hasNextPage()`/`getNextPage()` |
| Totals | `getNumResults()`, `hasToPaginate()` (`numResults > pageSize`) |
| Results | `getResults(): Traversable`, `count()`, `getIterator()` (implements `Countable`, `IteratorAggregate`) |
| Pager window | `calculateStartAndEndPage()`, then `getStartPage()`/`getEndPage()` |

### `getLastPage()` vs `getNumberOfPages()` — near-duplicate, different edge case

Both compute `ceil($numResults / $pageSize)`, but `getNumberOfPages()` clamps to a **minimum of 1** (used by
`hasNextPage()`'s bound indirectly via `calculateStartAndEndPage()`'s overflow logic), while `getLastPage()` can
return `0` when there are zero results. `hasNextPage()` compares against `getLastPage()`, not
`getNumberOfPages()` — with zero results, `getLastPage()` is `0` and `currentPage` (minimum `1`), so `hasNextPage()`
is always `false`, which is correct, but the two methods are not interchangeable if extending this class.

### `calculateStartAndEndPage()` must be called explicitly

Not invoked automatically by any getter — `getStartPage()`/`getEndPage()` return uninitialized state (typed property
error) unless this runs first. It computes a ±3-page window around `currentPage`, then if the window underflows below
page 1 or overflows past the last page, it shifts the **opposite** bound to try to preserve a same-size window
(clamped again to the valid range). `PaginatorExtension::renderPagination()` is the only caller in this bundle.

## Twig integration

`src/Twig/PaginatorExtension.php` — `#[AsTwigFunction('pagination', needsEnvironment: true, isSafe: ['html'])]`
registers the `pagination(paginator)` function used in templates.

- Throws `RuntimeException` if called from within a sub-request (`RequestStack::getParentRequest() !== null`) —
  the current route/query string can't be reliably attributed to the "real" page in that case, so pagination links
  can't be built.
- Reconstructs pager link parameters by merging `$request->query->all()` (query string, e.g. filters/sort) with
  `$request->attributes->get('_route_params', [])` (path parameters) — this is why the pager keeps existing query
  params when changing pages, without any component-level configuration.
- Calls `$paginator->calculateStartAndEndPage()` itself before rendering — callers never need to call it.
- Renders the `pager` block of the bundle's fixed template `@SumoCodersFrameworkCore/Twig/pagination.html.twig` via
  `$env->load(...)->renderBlock('pager', [...])` rather than a full template render — the bundle owns the pager
  markup; there's no override hook exposed through this extension (override the template path in the consuming app's
  Twig namespace to customize markup).

## Adding a new option

`pageSize` is currently the only constructor-configurable knob (`new Paginator($queryBuilder, pageSize: 10)`). Any new
per-instance behavior (e.g. a configurable window size for `calculateStartAndEndPage()`, currently hardcoded to `±3`)
would be a constructor parameter on this class — there's no attribute/config layer for pagination like there is for
breadcrumbs/titles.
