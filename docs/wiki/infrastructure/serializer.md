[Back to index](index.md)

# Serializer depth/circular-reference handlers

Prevents infinite recursion when the Symfony Serializer walks bidirectional Doctrine entity relations, by emitting
just the related entity's id instead of re-serializing (or infinitely recursing into) the full object graph.

Code: `src/Serializer/CircularReferenceHandler.php`, `src/Serializer/MaxDepthHandler.php`

## Workflow

- Both classes are invokable (`__invoke()`), matching the callable shapes the Symfony Serializer's
  `AbstractObjectNormalizer` expects for the `circular_reference_handler` context option and the
  `AbstractNormalizer::MAX_DEPTH_HANDLER` context option (used together with `#[MaxDepth]` on a property).
- Both resolve an id the same way: reflect over the object's properties, find the first one carrying Doctrine's
  `#[Id]` attribute (`Doctrine\ORM\Mapping\Id`), and return `(string) $property->getValue($object)`. No `#[Id]`
  property found → returns `null`.
- `MaxDepthHandler::__invoke()` receives the full normalizer signature
  (`$innerObject, $outerObject, $attributeName, $format, $context`) but only `$innerObject` is used — the rest exist
  purely to satisfy the shape Symfony invokes it with.

## Where it is used

- Neither class is registered as a service in `config/services.php`, nor referenced anywhere else in this repo (verified
  by grep) — the bundle ships them as plain utility classes, not wired-up behavior.
- A consuming application opts in explicitly, e.g. in its own `config/packages/framework.yaml`:
  `framework.serializer.circular_reference_handler: SumoCoders\FrameworkCoreBundle\Serializer\CircularReferenceHandler`,
  and by passing `AbstractNormalizer::MAX_DEPTH_HANDLER => new MaxDepthHandler()` in the normalization context (or
  wiring it as the `serializer.normalizer.object`'s default context) alongside `#[MaxDepth(n)]` on entity properties.

## Edge cases

- An entity with no `#[Id]`-attributed property (or a non-Doctrine object) silently serializes to `null` in that
  slot rather than throwing — easy to miss in a JSON response during debugging.
- The `getId()` reflection logic is duplicated verbatim in both classes; there's no shared trait/base class, so a
  fix to one (e.g. supporting composite keys) needs to be mirrored in the other.
