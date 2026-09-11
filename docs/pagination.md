# Pagination

`Paginator` wraps a Doctrine `QueryBuilder` and handles page math, result slicing, and iteration. The default page size
is 30.

## Usage

### Repository

Return a `Paginator` from the repository method. Do not call `paginate()` here, the controller does that.

```php
<?php

namespace App\Repository;

use App\Entity\Item;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SumoCoders\FrameworkCoreBundle\Pagination\Paginator;

class ItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Item::class);
    }

    public function getPaginated(): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('i')
            ->orderBy('i.name', 'ASC');

        return new Paginator($queryBuilder);
    }
}
```

### Controller

Call `paginate()` with the current page number from the query string:

```php
<?php

namespace App\Controller\Item;

use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/items', name: 'item_index')]
final class Index extends AbstractController
{
    public function __invoke(Request $request, ItemRepository $itemRepository): Response
    {
        $items = $itemRepository->getPaginated()
            ->paginate($request->query->getInt('page', 1));

        return $this->render('item/index.html.twig', [
            'items' => $items,
        ]);
    }
}
```

### Template

The `Paginator` is iterable and countable. Use the `pagination()` Twig function to render the pager widget:

```twig
{% if items|length > 0 %}
    {% for item in items %}
        <div>{{ item.name }}</div>
    {% endfor %}
{% else %}
    {{ include('partials/no-results.html.twig') }}
{% endif %}

{% if items.hasToPaginate %}
    <div class="d-flex justify-content-center">
        {{ pagination(items) }}
    </div>
{% endif %}
```

## `Paginator` API reference

| Method                       | Return type   | Description                                                                |
|------------------------------|---------------|----------------------------------------------------------------------------|
| `paginate(int $page = 1)`    | `self`        | Executes the query for the given page; returns `$this`                     |
| `getCurrentPage()`           | `int`         | Current page number                                                        |
| `getLastPage()`              | `int`         | Last page number (= total pages)                                           |
| `getPageSize()`              | `int`         | Items per page (default 30)                                                |
| `hasPreviousPage()`          | `bool`        | Whether a previous page exists                                             |
| `getPreviousPage()`          | `int`         | Previous page number (minimum 1)                                           |
| `hasNextPage()`              | `bool`        | Whether a next page exists                                                 |
| `getNextPage()`              | `int`         | Next page number (maximum last page)                                       |
| `hasToPaginate()`            | `bool`        | Whether there is more than one page                                        |
| `getNumResults()`            | `int`         | Total number of results across all pages                                   |
| `getResults()`               | `Traversable` | Results for the current page                                               |
| `calculateStartAndEndPage()` | `void`        | Populates `startPage`/`endPage` (±3 around current page) for pager UI      |
| `getStartPage()`             | `int`         | First page number in the pager window (after `calculateStartAndEndPage()`) |
| `getEndPage()`               | `int`         | Last page number in the pager window (after `calculateStartAndEndPage()`)  |

Custom page size:

```php
return new Paginator($queryBuilder, pageSize: 10);
```

## Sorting

Add an `orderBy` to the query builder and pass the sort direction from the request:

```php
public function getPaginated(string $sortField = 'name', string $sortDirection = 'ASC'): Paginator
{
    $allowedFields = ['name', 'email', 'createdAt'];
    if (!in_array($sortField, $allowedFields, true)) {
        $sortField = 'name';
    }

    $queryBuilder = $this->createQueryBuilder('u')
        ->orderBy('u.' . $sortField, $sortDirection === 'DESC' ? 'DESC' : 'ASC');

    return new Paginator($queryBuilder);
}
```

Controller:

```php
$users = $userRepository->getPaginated(
    $request->query->get('sort', 'name'),
    $request->query->get('direction', 'ASC'),
)->paginate($request->query->getInt('page', 1));
```

## Filters with session persistence

Without session storage, the filter resets when the user navigates to page 2. Store filter data in the session to
persist it across page requests.

```php
<?php

use App\Form\UserFilterType;
use App\Form\UserFilterData;

$filterData = $request->getSession()->has('user_filter')
    ? unserialize($request->getSession()->get('user_filter'))
    : new UserFilterData();

$form = $this->createForm(UserFilterType::class, $filterData);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $filterData = $form->getData();
    $request->getSession()->set('user_filter', serialize($filterData));
}

$users = $userRepository->getFiltered($filterData)
    ->paginate($request->query->getInt('page', 1));
```

To reset the filter, remove the session key:

```php
$request->getSession()->remove('user_filter');
```

## Troubleshooting

- **Total count is wrong with JOINs**: the paginator sets `HINT_DISTINCT => false` when no JOINs are present. With
  JOINs, ensure your query does not produce duplicate root entities
- **`paginate()` not called**: always call `paginate()` before passing the paginator to the template; calling only the
  constructor does not execute the query
- **Page parameter missing**: use `$request->query->getInt('page', 1)` so an absent `?page=` defaults to page 1
