[Back to index](index.md)

# Belgium postcode

A `ChoiceType`-based form field restricted to valid Belgian postcode/municipality pairs, backed by a static resource
list rather than free text, so postcodes can't be typo'd or made up.

Primary code reference: `src/Form/Type/BelgiumPostCodeType.php`

## Workflow

- `BelgiumPostCodeType::getParent()` returns `ChoiceType::class` — it is a choice field, not a new widget.
- `choice_loader` builds an `IntlCallbackChoiceLoader` from `array_flip(BelgiumPostCodes::getNames())`.
  `BelgiumPostCodes::getNames()` (`src/Intl/BelgiumPostCodes.php`) reads the `Names` entry of the compiled resource
  bundle at `src/Intl/Resources/data/belgiumpostcodes/nl.php`, which maps `"<postcode>|<municipality>"` → a display
  label (`"<postcode> - <municipality>"`). Flipping it produces the `label => choiceValue` array `ChoiceType`
  expects, where the choice value is exactly `"<postcode>|<municipality>"`.
- A `CallbackTransformer` converts between that string key and a `BelgiumPostCode` value object
  (`src/ValueObject/BelgiumPostCode.php`, a `readonly` class with `postcode`/`municipality` and `__toString()`):
  transform splits on `|`; reverseTransform (`explode('|', $postcodeKey, 2)`) rebuilds the VO. The choice value format
  and the VO transform are coupled — changing one without the other breaks round-tripping.
- `getBlockPrefix()` returns `sumoBelgiumPostCode`; no dedicated Twig block exists for it in
  `templates/Form/fields.html.twig`, so it renders through the standard `ChoiceType` widget blocks.
- `choice_loader` throws `LogicException` at option-resolution time if `symfony/intl` isn't installed
  (`class_exists(Intl::class)` guard) — a clear signal if this type is used in a project missing that dependency.

## Data source / edge cases

- The resource file (`nl.php`) is sourced from bpost's postcode validation tool and includes non-geographic
  "postcodes" for large recipients (embassies, parliament chambers, specific companies) alongside real
  municipalities — e.g. `1008|Kamer van Volksvertegenwoordigers`. Treat the choice list as "valid postal codes",
  not strictly "municipalities".
- `BelgiumPostCodes::getNames()` always reads the `'nl'` locale bucket (`self::readEntry(['Names'], 'nl', false)`)
  regardless of the request locale — there is no French/German label variant, even in a multi-locale app.
- `choice_translation_domain` is forced to `false`, so labels are never passed through the translator; the raw
  `"<postcode> - <municipality>"` string is shown as-is.

## Where it is used

Add a `BelgiumPostCode` typed property to a DTO/entity and add the field with
`$builder->add('postcode', BelgiumPostCodeType::class)` — see `docs/forms.md` for the full usage example. No
`file_class`/`image_class`-style required option; all defaults are self-contained.
