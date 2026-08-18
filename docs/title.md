# Page title

Sets the page `<title>` and `<h1>` from `#[Title]` attributes or, when absent, from the breadcrumb trail. Available in
all Twig templates as the `pageTitle` variable.

## `#[Title]` options

| Parameter | Type          | Default  | Description                                                                                                           |
|-----------|---------------|----------|-----------------------------------------------------------------------------------------------------------------------|
| `title`   | `string`      | required | Page title. Supports `{param}` (scalar) and `{object.property}` (object) interpolation. Passed through the translator |
| `parent`  | `array\|null` | `null`   | Prepends the title of another route. Keys: `name` (required), `parameters` (optional array)                           |
| `extend`  | `bool`        | `true`   | When `true`, appends ` - <site_title>`. When `false`, uses the string verbatim                                        |

The attribute can target a method or a class, and is **not repeatable**. For invokable controllers, prefer the
class-level placement; when both are present, the method-level attribute wins.

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

For invokable controllers, prefer placing `#[Route]` and `#[Title]` on the class rather than on `__invoke`:

```php
#[Route('/my-page', name: 'my_page')]
#[Title('My Page Title')]
class MyPageController
{
    public function __invoke(): Response
    {
        // ...
    }
}
```

Output: `My Page Title - My Application`

The title string is passed through the translator, so translation keys work:

```yaml
# translations/messages+intl-icu.en.yaml
page.my_page: 'My Page Title'
```

```php
#[Route('/my-page', name: 'my_page')]
#[Title('page.my_page')]
class MyPageController
{
    public function __invoke(): Response
    {
        // ...
    }
}
```

### Class-level attribute

`#[Title]` is preferably placed on the class rather than the method for invokable controllers. The class-level
attribute is only used as a fallback for `__invoke`; on a multi-action controller, keep `#[Title]` on each relevant
method instead:

```php
#[Title('My Page Title')]
class MyController
{
    public function __invoke(): Response
    {
        // ...
    }
}
```

### With a parent route

Pass `['name' => 'route_name']` to append the parent route's title to the chain:

```php
#[Route('/detail', name: 'detail_route')]
#[Title('Detail', ['name' => 'overview_route'])]
class DetailController
{
    public function __invoke(): Response
    {
        // ...
    }
}
```

Output: `Detail - Overview - My Application`

The parent chain is resolved recursively: if the parent route also has a `#[Title]` with its own parent, that is
included too.

### Dynamic titles

Reference a controller argument by name using `{param}`:

```php
#[Route('/edit/{name}', name: 'edit_item')]
#[Title('Edit {name}')]
class EditController
{
    public function __invoke(string $name): Response
    {
        // ...
    }
}
```

Access a property of an object argument using `{object.property}`:

```php
#[Route('/blog/{slug}', name: 'blog_detail')]
#[Title('{blog.title}')]
class BlogDetailController
{
    public function __invoke(
        #[MapEntity(mapping: ['slug' => 'slug'])]
        Blog $blog,
    ): Response {
        // ...
    }
}
```

Dynamic parameters are resolved from the named controller arguments. If a placeholder is not found, an exception is
thrown.

### Disable automatic appending

Pass `extend: false` to set the title verbatim, with no translation, no parent chain, and no site title appended:

```php
#[Title('Exact Title', extend: false)]
class ExactTitleController
{
    public function __invoke(): Response
    {
        // ...
    }
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
<h1>{{ pageTitle }}</h1>
```

## Troubleshooting

- **`{param}` not resolving**: the placeholder must match the exact name of a controller argument. For objects, use
  `{object.property}` not `{object}`
- **Title missing site name**: verify `fallbacks.site_title` is set in `parameters` in `config/services.yaml`
- **Parent chain not working**: each route in the chain must exist and have `#[Title]` or `#[Breadcrumb]` attributes;
  the chain resolves by dispatching a subrequest to fetch the parent's title
- **`extend: false` still appends site title**: check that `extend:` is passed as a named argument:
  `#[Title('My Title', extend: false)]`
