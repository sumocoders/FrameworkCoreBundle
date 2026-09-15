# Serializer helpers

Two opt-in helper classes for the Symfony Serializer: `CircularReferenceHandler` and `MaxDepthHandler`. Both prevent
infinite recursion when serializing bidirectional Doctrine entity relations, by emitting just the related entity's id
instead of re-serializing (or infinitely recursing into) the full object graph.

Code: `src/Serializer/CircularReferenceHandler.php`, `src/Serializer/MaxDepthHandler.php`

## Prerequisites

- `symfony/serializer` installed and configured (included by default in most Symfony projects).
- Entities use Doctrine's `#[Id]` attribute (`Doctrine\ORM\Mapping\Id`) on their identifier property — that's how
  both handlers resolve the id to emit.

Neither class is registered as a service by this bundle. They are plain classes matching the callable shapes the
Symfony Serializer expects; a consuming application wires them in explicitly.

## Usage

### `CircularReferenceHandler`

Matches the shape expected by the `circular_reference_handler` context option. Wire it into the serializer config in
a consuming app's `config/packages/framework.yaml`:

```yaml
# config/packages/framework.yaml
framework:
    serializer:
        circular_reference_handler: SumoCoders\FrameworkCoreBundle\Serializer\CircularReferenceHandler
```

With this in place, whenever the serializer would otherwise recurse back into an object it has already visited (e.g.
`Order` → `Customer` → `Order`), it calls `CircularReferenceHandler` instead and emits the offending object's id.

### `MaxDepthHandler`

Matches the shape expected by `AbstractNormalizer::MAX_DEPTH_HANDLER`, used together with `#[MaxDepth(n)]` on an
entity property. Add the attribute:

```php
<?php

namespace App\Entity;

use Symfony\Component\Serializer\Annotation\MaxDepth;

class Order
{
    #[MaxDepth(1)]
    private Customer $customer;
}
```

Then pass a `MaxDepthHandler` instance in the normalization context, alongside `enable_max_depth`:

```php
<?php

use SumoCoders\FrameworkCoreBundle\Serializer\MaxDepthHandler;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

final class OrderExport
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    public function toJson(Order $order): string
    {
        return $this->serializer->serialize($order, 'json', [
            AbstractNormalizer::ENABLE_MAX_DEPTH => true,
            AbstractNormalizer::MAX_DEPTH_HANDLER => new MaxDepthHandler(),
        ]);
    }
}
```

Once `customer` is nested past the configured `#[MaxDepth(1)]`, the serializer calls `MaxDepthHandler` instead of
continuing to normalize it, and the id is emitted in that slot.

## How ids are resolved

Both classes resolve an id the same way: reflect over the object's properties, find the first one carrying
Doctrine's `#[Id]` attribute, and return `(string) $property->getValue($object)`.

`MaxDepthHandler::__invoke()` receives the full normalizer signature
(`$innerObject, $outerObject, $attributeName, $format, $context`), but only `$innerObject` is used to resolve the id
— the rest of the parameters exist purely to satisfy the shape Symfony invokes it with.

## Troubleshooting

- **A relation silently serializes to `null` instead of an id**: the object being handled has no property carrying
  `#[Id]` (or it isn't a Doctrine entity at all). Both handlers return `null` in that case rather than throwing, so
  this is easy to miss until you notice `null` in a JSON response where an id was expected.
- **`MaxDepthHandler` never gets called**: check that `AbstractNormalizer::ENABLE_MAX_DEPTH => true` is present in
  the context — `#[MaxDepth]` is only enforced when that option is enabled.
- **`CircularReferenceHandler` never gets called**: confirm `framework.serializer.circular_reference_handler` is set
  in the consuming app's config — this bundle does not register it for you.
