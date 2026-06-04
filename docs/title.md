# Page title

## Resolution order

The `PageTitle` service resolves the title in this order:

1. A title set explicitly via `#[Title]` on the controller method.
2. The breadcrumb trail in reverse order, joined with ` - `, appended with the site title.
3. The `fallback.site_title` value alone if no breadcrumbs are present.

## Configuring the site title

Set `fallback.site_title` in your `services.yaml`:

```yaml
parameters:
    fallbacks:
        site_title: 'My Application'
```

## The `#[Title]` attribute

```php
use SumoCoders\FrameworkCoreBundle\Attribute\Title;
```

### Basic usage

```php
#[Title('My Page Title')]
public function __invoke(): Response
{
    // ...
}
```

Output: `My Page Title - My Application`

The title string is passed through the translator, so translation keys work:

```yaml
# translations/messages.en.yaml
page.my_page: 'My Page Title'
```

```php
#[Title('page.my_page')]
public function __invoke(): Response
{
    // ...
}
```

### With a parent route

Pass `['name' => 'route_name']` to append the parent route's title to the chain:

```php
#[Title('Detail', ['name' => 'overview_route'])]
public function __invoke(): Response
{
    // ...
}
```

Output: `Detail - Overview - My Application`

The parent chain is resolved recursively: if the parent route also has a `#[Title]` with its own parent, that is included too.

### Dynamic titles

Reference a controller argument by name using `{param}`:

```php
#[Title('Edit {name}')]
public function __invoke(string $name): Response
{
    // ...
}
```

Access a property of an object argument using `{object.property}`:

```php
#[Title('{blog.title}')]
public function __invoke(
    #[MapEntity(mapping: ['slug' => 'slug'])]
    Blog $blog,
): Response {
    // ...
}
```

Dynamic parameters are resolved from the named controller arguments. If a placeholder is not found, an exception is thrown.

### Disable automatic appending

Pass `extend: false` to set the title verbatim, with no translation, no parent chain, and no site title appended:

```php
#[Title('Exact Title', extend: false)]
public function __invoke(): Response
{
    // ...
}
```

Output: `Exact Title`

## Using `PageTitle` directly

Inject `PageTitle` to set or get the title from a service or Twig template:

```php
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;

class MyService
{
    public function __construct(private PageTitle $pageTitle) {}

    public function doSomething(): void
    {
        $this->pageTitle->setTitle('Custom Title');
    }
}
```

In Twig, `PageTitle` is available as a string (via `__toString`):

```twig
<title>{{ pageTitle }}</title>
```
