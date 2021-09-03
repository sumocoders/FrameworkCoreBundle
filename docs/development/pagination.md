# Using pagination

Pagination is a nice way to handle large amounts of data over multiple pages. The core bundle has a Paginator class (similar to Pagerfanta), that does most of the heavy lifting.

## Usage
Define the Paginator object in your repository, where you pass the QueryBuilder object straight to it.
### Repository

```php
    use SumoCoders\FrameworkCoreBundle\Pagination\Paginator;
    
    public function getPaginatedItems(): Paginator
    {
        $queryBuilder = $this->createQueryBuilder('i')
                ->where('i.name LIKE :term')
                ->setParameter('term', 'foo')
                ->orderBy('i.name');

        return new Paginator($queryBuilder);
    }
```
## Controller

In your controller, use the `paginate` method on it to set the correct page. You can also extend this with sorting GET parameters that you pass to your method in the repository. Since the pagination works on a QueryBuilder object, al sorting must be done with orderBy's.

```php
<?php

namespace SumoCoders\FrameworkCoreBundle\Controller;

use App\Repository\ItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class ItemController extends AbstractController
{
    /**
      * @Route("/items", name="item_index")
      */
     public function __invoke(
        Request $request,
        ItemRepository $itemRepository
    ): Response {
        $paginatedItems = $itemRepository->getPaginatedItems();

        $paginatedItems->paginate($request->query->getInt('page', 1));

        return $this->render('items/index.html.twig', [
            'items' => $paginatedItems,
        ]);
    }
}
```

## Template

In your template, you have access to a Twig extension called `pagination` to render a clean pagination widget.

The paginated object, in this case `items` is an iterator, so you can count it/loop over it to get the results of the query.

```twig
{% if items|length > 0 %}
    {% for item in items %}
        <ul>
            <li>{{ item.id }}</li>
        </ul>
    {% endfor %}
{% endif %}

{% if items.hasToPaginate %}
    <div class="d-flex justify-content-center">
        {{ pagination(items) }}
    </div>
{% endif %}
```
