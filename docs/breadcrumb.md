# Breadcrumbs

Populates a `BreadcrumbTrail` service from `#[Breadcrumb]` attributes on controller classes and methods. The trail is
available for rendering in Twig on every request.

## Prerequisites

No additional configuration required. `BreadcrumbListener` fires automatically on `kernel.controller_arguments`.

## `#[Breadcrumb]` options

| Parameter    | Type          | Default  | Description                                                                                      |
|--------------|---------------|----------|--------------------------------------------------------------------------------------------------|
| `title`      | `string`      | required | Crumb label. Supports `{object.property}` interpolation. Passed through the translator           |
| `route`      | `array\|null` | `null`   | Makes the crumb a link. Keys: `name` (required), `parameters` (optional array)                   |
| `parent`     | `array\|null` | `null`   | Prepends the full trail of another route. Keys: `name` (required), `parameters` (optional array) |
| `parameters` | `array`       | `[]`     | Translation parameters. Values are `object.property` paths resolved from controller arguments    |

The attribute targets both **methods** and **classes**, and is **repeatable**. Multiple `#[Breadcrumb]` on the same
element are added in declaration order.

## Rendering in Twig

```twig
{% for crumb in breadcrumbTrail %}
    {% if loop.last %}
        <li class="breadcrumb-item active">{{ crumb.title|trans }}</li>
    {% else %}
        <li class="breadcrumb-item">
            {% if crumb.hasRoute %}
                <a href="{{ path(crumb.route.name, crumb.route.parameters ?? {}) }}">{{ crumb.title|trans }}</a>
            {% else %}
                {{ crumb.title|trans }}
            {% endif %}
        </li>
    {% endif %}
{% endfor %}
```

`breadcrumbTrail` is available automatically in all templates via the bundle's Twig extension.

## Basics

Add a `#[Breadcrumb]` attribute to a controller to register a crumb. The attribute is repeatable. Each one
appends a crumb to the trail in declaration order. For invokable controllers, prefer placing `#[Route]` and
`#[Breadcrumb]` on the class rather than on `__invoke`.

```php
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb;
```

Single crumb:

```php
#[Route('/books', name: 'books_overview')]
#[Breadcrumb('books')]
class BooksOverviewController
{
    public function __invoke(): Response
    {
    }
}
```

Chained crumbs on one controller:

```php
#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('books')]
#[Breadcrumb('genres')]
class GenresOverviewController
{
    public function __invoke(): Response
    {
    }
}
```

## Class-level attributes

`#[Breadcrumb]` is preferably placed on the class rather than the method for invokable controllers. Class attributes
are only picked up for `__invoke` controllers, or for named methods that also have at least one `#[Breadcrumb]`
attribute of their own - so this convention doesn't apply to multi-action controllers, where `#[Breadcrumb]` stays
on the relevant method.

```php
#[Route('/books', name: 'books_overview')]
#[Breadcrumb('books')]
class BooksController
{
    public function __invoke(): Response
    {
    }
}
```

## Linked breadcrumbs (`route:`)

Pass `route:` to make the crumb a link. Required route parameters are automatically resolved from the current
controller's named arguments and request attributes. You do not need to specify them manually.

```php
#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('books', route: ['name' => 'books_overview'])]
#[Breadcrumb('genres')]
class GenresOverviewController
{
    public function __invoke(): Response
    {
    }
}
```

When a parameter cannot be resolved automatically, you can supply a fixed value. This is rarely needed and ties the
breadcrumb to a hardcoded value:

```php
#[Breadcrumb('section', route: ['name' => 'section_detail', 'parameters' => ['id' => 42]])]
```

## Parent route chaining (`parent:`)

Pass `parent:` to automatically prepend the full breadcrumb trail of another route. The parent route must have its own
`#[Breadcrumb]` attribute. Parameters are resolved from the current request.

```php
#[Route('/books', name: 'books_overview')]
#[Breadcrumb('books')]
class BooksOverviewController
{
    public function __invoke(): Response
    {
    }
}

#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('genres', parent: ['name' => 'books_overview'])]
class GenresOverviewController
{
    public function __invoke(): Response
    {
    }
}
```

The chain is resolved recursively, so parents of parents work as long as each route in the chain has `#[Breadcrumb]`.

## Dynamic titles from object properties

Use `{object.property}` to read a value from a controller argument at request time:

```php
#[Route('/books/{book}', name: 'book_detail')]
#[Breadcrumb('books', route: ['name' => 'books_overview'])]
#[Breadcrumb('{book.title}')]
class BookDetailController
{
    public function __invoke(Book $book): Response
    {
    }
}
```

This also works when combined with `parent:`, as long as the required route parameters are present in the URL:

```php
// ! /{author} must be in the route for parameter resolution to work
#[Route('/{author}/{book}', name: 'book_detail')]
#[Breadcrumb('{book.title}', parent: ['name' => 'author_detail'])]
class BookDetailController
{
    public function __invoke(Author $author, Book $book): Response
    {
    }
}
```

> Scalar parameters (e.g. `string $name`) cannot be used with the `{name}` syntax. Only objects with a property path
> are supported. Using a scalar silently omits the breadcrumb.

## Translations

All breadcrumb titles pass through the `|trans` Twig filter when rendered. Translation keys work out of the box:

```yaml
# translations/messages+intl-icu.en.yaml
breadcrumb.books: 'Books'
```

```php
#[Breadcrumb('breadcrumb.books')]
```

For parameterized translations, pass `parameters:` as an array where keys are the translation placeholders and values
are `object.property` paths resolved from the current named arguments:

```yaml
breadcrumb.author_detail: 'Author: {name}'
```

```php
#[Route('/author/{author}', name: 'author_detail')]
#[Breadcrumb('breadcrumb.author_detail', parameters: ['name' => 'author.name'])]
class AuthorDetailController
{
    public function __invoke(Author $author): Response
    {
    }
}
```

## Full example

Trail: Authors > J.K. Rowling > Harry Potter

```php
#[Route('/authors', name: 'author_overview')]
#[Breadcrumb('breadcrumb.authors')]
class AuthorOverviewController
{
    public function __invoke(): Response
    {
    }
}

#[Route('/authors/{author}', name: 'author_detail')]
#[Breadcrumb('{author.name}', parent: ['name' => 'author_overview'])]
class AuthorDetailController
{
    public function __invoke(Author $author): Response
    {
    }
}

#[Route('/authors/{author}/{book}', name: 'book_detail')]
#[Breadcrumb('{book.title}', parent: ['name' => 'author_detail'])]
class BookDetailController
{
    public function __invoke(Author $author, Book $book): Response
    {
    }
}
```

```yaml
breadcrumb.authors: 'Authors'
```

## Troubleshooting

- **Breadcrumb not appearing**: on a multi-action controller, a class-level `#[Breadcrumb]` is only picked up for
  `__invoke` or for methods that have their own `#[Breadcrumb]` attribute - verify the matched method qualifies
- **`{object.property}` shows literally**: scalars (e.g. `string $name`) cannot be interpolated; only object arguments
  with accessible properties work
- **Parent chain stops early**: every route in the chain must have its own `#[Breadcrumb]` attribute; missing one
  breaks the recursive resolution
- **Translation key not found**: breadcrumb titles are translated using the default domain; add the key to
  `translations/messages+intl-icu.<locale>.yaml`
