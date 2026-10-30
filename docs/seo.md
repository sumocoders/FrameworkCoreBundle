# SEO

Per-entity SEO metadata: a `Meta` embeddable you hang on your own entity, a ready-made form to edit it,
and Twig output for meta tags and schema.org structured data. The bundle picks the right `Meta` up
automatically on the entity's public page and renders nothing anywhere else.

## Prerequisites

The bundle ships with FrameworkCoreBundle; there is nothing extra to install. Three things need wiring
once per application.

**1. The `seo-meta-form` Stimulus controller**, like the other bundle controllers:

```php
// importmap.php
'sumocoders/SeoMetaForm' => ['path' => './vendor/sumocoders/framework-core-bundle/assets-public/controllers/seo_meta_form_controller.js'],
```

```js
// assets/bootstrap.js
import SeoMetaForm from 'sumocoders/SeoMetaForm'

app.register('seo-meta-form', SeoMetaForm)
```

**2. The bundle's form theme**, so `form_row(form.seo)` renders the SEO card:

```yaml
# config/packages/twig.yaml
twig:
    form_themes:
        - "bootstrap_5_layout.html.twig"
        - "@SumoCodersFrameworkCore/Form/fields.html.twig"
```

**3. Bootstrap's collapse JavaScript and Bootstrap Icons.** The SEO card is a collapsible
`card` whose header uses `data-bs-toggle="collapse"` and a `bi-chevron-down` icon. Both are present in
the standard skeleton; without them the card renders but will not fold.

No Doctrine configuration is needed. `SumoCodersFrameworkCoreExtension::load()` prepends the ORM
mapping for `SumoCoders\FrameworkCoreBundle\Entity`, so `Meta` is mapped as soon as the bundle is
enabled.

---

## Linking to an entity

`Meta` is an embeddable, not an entity of its own. Embed it with `#[ORM\Embedded]` so its columns live
on your own table — no join, no second table, no cascade to configure. Add `#[HasMeta]` so the bundle
pushes the `Meta` instance into the request context automatically.

The entity also needs a slug: the bundle only renders meta tags on the public, slug-based page of the
entity (see below).

```php
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Slug;
use SumoCoders\FrameworkCoreBundle\Attribute\HasMeta;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;

#[ORM\Entity(repositoryClass: BlogRepository::class)]
#[ORM\Table(name: 'blog')]
#[HasMeta]
class Blog
{
    #[ORM\Column(length: 255, unique: true)]
    #[Slug(fields: ['title'])]
    private ?string $slug = null;

    #[ORM\Embedded(class: Meta::class)]
    private Meta $meta;

    public function __construct(string $title, string $content, Meta $meta)
    {
        $this->title = $title;
        $this->content = $content;
        $this->meta = $meta;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function getMeta(): Meta
    {
        return $this->meta;
    }

    public function setMeta(Meta $meta): void
    {
        $this->meta = $meta;
    }
}
```

This adds three columns to the owner's own table, prefixed with the property name:

| Column | Type |
|--------|------|
| `meta_page_title` | `VARCHAR(100)` nullable |
| `meta_meta_description` | `VARCHAR(255)` nullable |
| `meta_robots` | `JSON` nullable |

Pass `columnPrefix` to `#[ORM\Embedded]` if you want different names, or `columnPrefix: false` to drop
the prefix.

Keep the property non-nullable. Doctrine always instantiates an embeddable during hydration — even when
all three columns are `NULL` — so `getMeta()` never returns `null` for an entity loaded from the
database. Only new objects need a `Meta` of their own, which is what the constructor is for.

`#[HasMeta]` takes two optional arguments, both naming a getter on the entity:

| Argument | Default | Returns |
|----------|---------|---------|
| `meta` | `getMeta` | the `Meta` instance |
| `slug` | `getSlug` | the slug used in the URL |

```php
#[HasMeta(meta: 'getSeoMeta', slug: 'getUrlSlug')]
```

### Only on public pages

`MetaContextListener` only pushes the `Meta` into the context when the **route parameter value equals
the entity's slug**. Everything else renders no meta tags — which is what keeps them off admin pages.

```php
#[Route('/blog/{blog}', name: 'blog_show')]
#[Breadcrumb('{blog.title}')]
final class ShowController extends AbstractController
{
    public function __invoke(
        #[MapEntity(mapping: ['blog' => 'slug'])]
        Blog $blog,
    ): Response {
        // ...
    }
}
```

Consequences worth knowing:

- An admin route that resolves the entity by id renders no meta tags: the id does not match the slug.
- A route that resolves by slug but names the parameter something the entity's slug does not match
  renders nothing either. The comparison is on the value, not the parameter name.

---

## Editing meta via a form

`SeoType` renders a complete, collapsible SEO card. Embed it in your own `FormType`:

```php
use SumoCoders\FrameworkCoreBundle\Form\Type\SeoType;

$builder->add('seo', SeoType::class);
```

Render it with a single line, exactly like any other field:

```twig
{{ form_row(form.seo) }}
```

That one line is enough because the bundle's form theme ships a `seo_widget` block, which renders the
card, the checkbox-prepended inputs and the Stimulus wiring. Register the theme as shown under
[Prerequisites](#prerequisites); without it `form_row(form.seo)` still renders, but falls back to plain
Bootstrap 5 markup — no card, no checkbox-unlock behaviour, no `pageTitle` mirroring.

The card starts expanded when any SEO value is already set, and collapsed when the entity has none.

### The robots fields

`SeoType` renders six fields, not three. Robots is split across three controls so that mutually
exclusive directives cannot both be picked:

| Field | Control | Choices |
|-------|---------|---------|
| `pageTitle` | text, gated by `overridePageTitle` | — |
| `metaDescription` | text, gated by `overrideMetaDescription` | — |
| `robotsIndex` | radios, optional | `Index`, `NoIndex` |
| `robotsFollow` | radios, optional | `Follow`, `NoFollow` |
| `robotsAdditional` | checkboxes, multiple | `NoArchive`, `NoSnippet`, `NoImageIndex` |

`Meta` itself stores one flat `list<RobotsDirective>`. `SeoDataTransferObject::fromMeta()` splits that
list across the three fields, and `applyTo()` recombines them. Nothing in the entity knows about the
split — it exists purely so the form cannot produce `index` and `noindex` at once.

### `pageTitle` mirroring

While its override checkbox is unchecked, `pageTitle` can mirror another field on the page live (e.g.
the entity's own title, so editors see a live preview of the default). Point it at that field with a
CSS selector via the `page_title_source_selector` option, and mark the source field with a plain
`data-*` attribute (not `id`, so it doesn't depend on Symfony's auto-generated form ids):

```php
$builder
    ->add('title', TextType::class, ['attr' => ['data-seo-title-field' => 'true']])
    ->add('content', TextareaType::class)
    ->add('seo', SeoType::class, ['page_title_source_selector' => '[data-seo-title-field]']);
```

Leave the option out (or empty) to skip mirroring entirely.

### Persisting the submitted data

`SeoType` binds to `SeoDataTransferObject`, not `Meta` directly. Add it as a property on your own DTO:

```php
use SumoCoders\FrameworkCoreBundle\DataTransferObject\SeoDataTransferObject;

class BlogDataTransferObject
{
    public function __construct(
        #[NotBlank]
        public string $title = '',
        #[NotBlank]
        public string $content = '',
        public SeoDataTransferObject $seo = new SeoDataTransferObject(),
    ) {
    }
}
```

When editing, populate it from the entity's existing `Meta`:

```php
final class UpdateBlogMessage extends BlogDataTransferObject
{
    public readonly int $id;

    public function __construct(Blog $blog)
    {
        $this->id = $blog->getId();

        parent::__construct(
            $blog->getTitle(),
            $blog->getContent(),
            SeoDataTransferObject::fromMeta($blog->getMeta()),
        );
    }
}
```

And apply it back in the handler:

```php
// creating — the entity has no Meta yet
$meta = new Meta();
$message->seo->applyTo($meta);
$this->repository->save(new Blog($message->title, $message->content, $meta));

// updating — apply onto the entity's existing Meta instance
$blog->update($message->title, $message->content);
$message->seo->applyTo($blog->getMeta());
$this->repository->save($blog);
```

`applyTo()` stores `null` for `pageTitle`/`metaDescription` when their override checkbox was left
unchecked — matching `Meta`'s own "unset means fall back elsewhere" behaviour. For `robots` it stores
an empty array when nothing was selected.

---

## Rendering meta tags

The bundle's `templates/head.html.twig` already calls `meta_tags()` inside its `meta` block, so an
application extending the bundle's base template gets meta tags with no work at all. It also falls back
to a site-wide `og:image` when the page did not supply one.

Only an application that does not extend the bundle's head needs to call it itself:

```twig
{{ meta_tags() }}
```

No `|raw` needed: the function is registered with `isSafe: ['html']`. When the request has no `Meta` in
context, it returns an empty string.

### Page title

`meta_tags()` does not emit a `<title>` tag. `Meta` only contributes `og:title` and `twitter:title`.
That split is what keeps a page from ending up with two `<title>` tags.

The `<title>` is set earlier instead: when `MetaContext::setMeta()` receives a `Meta` with a non-empty
`pageTitle`, it pushes that straight into the `PageTitle` service, during `kernel.controller_arguments`.
By the time any template renders, `page_title` already holds the SEO title — which matters, because
`head.html.twig` renders `<title>` several lines *above* the `meta_tags()` call.

When `pageTitle` is `null` or an empty string, `PageTitle` is left untouched and whatever the page
already set (an `#[Title]` attribute, the breadcrumb fallback) stays in place.

### Extending the `MetaTags` object

To extend the `MetaTags` object (e.g. add an image or Open Graph properties), call
`getMetaTagsInstance()` in your controller before rendering:

```php
$blog->getMeta()->getMetaTagsInstance()->image('https://example.com/image.jpg');
$blog->getMeta()->getMetaTagsInstance()->og('type', 'article');
```

`getMetaTagsInstance()` builds the object once and returns that same instance on every later call, so
calling it from several places is safe — the additions accumulate instead of resetting.

Uses the [`melbahja/seo`](https://github.com/melbahja/seo) API.

---

## Rendering schema.org structured data

Use the `schema_script` Twig filter to output a `<script type="application/ld+json">` block with
pretty-printed JSON-LD. Build a `Schema` object in your controller using the `melbahja/seo` API and pass
it to the template.

```php
use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\CreativeWork;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$schemaCreativeWork = new CreativeWork(
    props: [
        'name' => $blog->getTitle(),
        'url' => $this->generateUrl(
            'blog_show',
            ['blog' => $blog->getSlug()],
            UrlGeneratorInterface::ABSOLUTE_URL,
        ),
    ],
    type: 'BlogPosting',
);

if ($blog->getMeta()->getMetaDescription() !== null) {
    $schemaCreativeWork->description($blog->getMeta()->getMetaDescription());
}

return $this->render('blog/show.html.twig', [
    'blog' => $blog,
    'schema' => new Schema($schemaCreativeWork),
]);
```

In your template:

```twig
{{ schema|schema_script }}
```

The filter outputs pretty-printed JSON and is marked `isSafe: ['html']`, so no `|raw` is needed.

---

## `RobotsDirective` enum

`RobotsDirective` represents valid robots meta tag directives.

| Case | Value |
|------|-------|
| `Index` | `index` |
| `NoIndex` | `noindex` |
| `Follow` | `follow` |
| `NoFollow` | `nofollow` |
| `NoArchive` | `noarchive` |
| `NoSnippet` | `nosnippet` |
| `NoImageIndex` | `noimageindex` |

Conflicting combinations that must not be used together:

- `index` and `noindex`
- `follow` and `nofollow`

`SeoType` makes these unpickable by splitting them over separate radio groups, but nothing stops code
from setting both directly on the `Meta` value object:

```php
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;

$meta->setRobots([RobotsDirective::NoIndex, RobotsDirective::NoFollow]);
```

When `robots` is `null` (the default) or empty, no robots meta tag is rendered.

The enum implements `TranslatableInterface`, so passing a case to `trans` resolves
`seo.robots.<value>`.

---

## Translations

The bundle ships English and Dutch translations in `translations/messages.en.yml` and
`translations/messages.nl.yml`, in the `messages` domain:

| Key | Used for |
|-----|----------|
| `seo.card.title` | the SEO card header |
| `seo.label.*` | field labels (`pageTitle`, `metaDescription`, `robotsIndex`, `robotsFollow`, `robotsAdditional`) |
| `seo.help.*` | the help text under each field |
| `seo.robots.heading` | the "Robots" subheading inside the card |
| `seo.robots.none` | the empty-choice placeholder on both radio groups |
| `seo.robots.<value>` | one per `RobotsDirective` case |

Override any of them by defining the same key in your application's own `messages.*.yml`.

---

## Internals

- `MetaContextListener` is registered on `kernel.controller_arguments` at **priority `-2`**, one step
  after `TitleListener` (`-1`). That ordering is load-bearing: `TitleListener` writes the `#[Title]`
  value into `PageTitle` first, then `MetaContext::setMeta()` overwrites it with the SEO page title, so
  an explicitly configured SEO title always wins.
- The listener returns early for sub-requests, and for any route whose name starts with `_` (the
  profiler, `_wdt`, error routes).
- It walks `$event->getNamedArguments()` and `return`s after the **first** object argument carrying
  `#[HasMeta]`. A controller taking two such entities silently uses whichever comes first in the
  argument list.
- The slug gate compares `$request->attributes->getString($name)` — the route parameter named after the
  *argument* — against the entity's slug getter. When they differ, nothing is pushed and the page
  renders no meta tags.
- `Meta::getMetaTagsInstance()` memoises the `MetaTags` object on the embeddable instance, so controller
  additions made before rendering survive into `meta_tags()`. The instance is not a mapped column; it is
  rebuilt per request.
- `MetaContext` implements `ResetInterface`. `config/services.php` uses `->autoconfigure()`, which tags
  any `ResetInterface` implementation as `kernel.reset` — so a long-running worker (FrankenPHP,
  Messenger) cannot leak one request's `Meta` into the next.
- `MetaExtension` and `SchemaExtension` are attribute extensions: plain classes using
  `#[AsTwigFunction]` / `#[AsTwigFilter]` plus the `twig.attribute_extension` tag, like every other Twig
  extension in this bundle. Neither extends `AbstractExtension`, so neither has `getFunctions()` or
  `getFilters()` — read the attributes by reflection if you need to assert on them.
- The ORM mapping for `src/Entity` is prepended by `SumoCodersFrameworkCoreExtension::load()` under the
  alias `FrameworkCoreBundle`, which is why `Meta` needs no entry in an application's `doctrine.yaml`.
- The Stimulus controller disables rather than hides the gated inputs (`field.disabled = !checkbox.checked`),
  so unchecked values are never submitted — which is what lets `applyTo()` treat "unchecked" as `null`.

---

## Troubleshooting

- **No meta tags on a page**: the route parameter value does not equal the entity's slug. Check the
  entity resolves by slug (`#[MapEntity(mapping: ['blog' => 'slug'])]`), not by id. Admin routes
  resolving by id render nothing by design.
- **No meta tags, and the entity *is* resolved by slug**: the controller may take another `#[HasMeta]`
  object earlier in its argument list — only the first one is considered.
- **The SEO form renders as plain fields, no card**: `@SumoCodersFrameworkCore/Form/fields.html.twig` is
  not in `twig.form_themes`, see [Prerequisites](#prerequisites).
- **The checkbox does not unlock the field**: the `seo-meta-form` controller is not registered; check
  `importmap.php` and `assets/bootstrap.js`.
- **The card does not fold open or shut**: Bootstrap's collapse JavaScript is not loaded.
- **Title mirroring does nothing**: the selector matched no element. The controller logs
  `seo-meta-form: no element matches selector "..."` to the browser console — grep for `seo-meta-form`
  there. Make sure the source field carries the `data-*` attribute the selector looks for.
- **Two `<title>` tags**: the application renders its own `<title>` outside the bundle's `head` block.
  `meta_tags()` never emits one; override the `title` block instead.
- **The SEO page title is ignored, the `#[Title]` value wins**: `MetaContextListener` must run after
  `TitleListener`. Check it is still registered at priority `-2`.
