# Using the breadcrumb

## Basics

Add a `#[Breadcrumb]` attribute to a controller method to register a crumb. The attribute is repeatable — each one
appends a crumb to the trail in declaration order.

```php
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb;
```

Single crumb:

```php
#[Route('/books', name: 'books_overview')]
#[Breadcrumb('books')]
public function __invoke(): Response
{
}
```

Chained crumbs on one controller:

```php
#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('books')]
#[Breadcrumb('genres')]
public function __invoke(): Response
{
}
```

## Class-level attributes

`#[Breadcrumb]` can be placed on the class instead of the method. Class attributes are only picked up for `__invoke`
controllers, or for named methods that also have at least one `#[Breadcrumb]` attribute of their own.

```php
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
controller's named arguments and request attributes — you do not need to specify them manually.

```php
#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('books', route: ['name' => 'books_overview'])]
#[Breadcrumb('genres')]
public function __invoke(): Response
{
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
public function __invoke(): Response
{
}

#[Route('/books/genres', name: 'genres_overview')]
#[Breadcrumb('genres', parent: ['name' => 'books_overview'])]
public function __invoke(): Response
{
}
```

The chain is resolved recursively, so parents of parents work as long as each route in the chain has `#[Breadcrumb]`.

## Dynamic titles from object properties

Use `{object.property}` to read a value from a controller argument at request time:

```php
#[Route('/books/{book}', name: 'book_detail')]
#[Breadcrumb('books', route: ['name' => 'books_overview'])]
#[Breadcrumb('{book.title}')]
public function __invoke(Book $book): Response
{
}
```

This also works when combined with `parent:`, as long as the required route parameters are present in the URL:

```php
// ! /{author} must be in the route for parameter resolution to work
#[Route('/{author}/{book}', name: 'book_detail')]
#[Breadcrumb('{book.title}', parent: ['name' => 'author_detail'])]
public function __invoke(Author $author, Book $book): Response
{
}
```

> Scalar parameters (e.g. `string $name`) cannot be used with the `{name}` syntax — only objects with a property path
> are supported. Using a scalar silently omits the breadcrumb.

## Translations

All breadcrumb titles pass through the `|trans` Twig filter when rendered. Translation keys work out of the box:

```yaml
# translations/messages.en.yaml
breadcrumb.books: 'Books'
```

```php
#[Breadcrumb('breadcrumb.books')]
```

For parameterized translations, pass `parameters:` as an array where keys are the translation placeholders and values
are `object.property` paths resolved from the current named arguments:

```yaml
breadcrumb.author_detail: 'Author: %name%'
```

```php
#[Route('/author/{author}', name: 'author_detail')]
#[Breadcrumb('breadcrumb.author_detail', parameters: ['%name%' => 'author.name'])]
public function __invoke(Author $author): Response
{
}
```

## Full example

Trail: Authors > J.K. Rowling > Harry Potter

```php
#[Route('/authors', name: 'author_overview')]
#[Breadcrumb('breadcrumb.authors')]
public function __invoke(): Response
{
}

#[Route('/authors/{author}', name: 'author_detail')]
#[Breadcrumb('{author.name}', parent: ['name' => 'author_overview'])]
public function __invoke(Author $author): Response
{
}

#[Route('/authors/{author}/{book}', name: 'book_detail')]
#[Breadcrumb('{book.title}', parent: ['name' => 'author_detail'])]
public function __invoke(Author $author, Book $book): Response
{
}
```

```yaml
breadcrumb.authors: 'Authors'
```
