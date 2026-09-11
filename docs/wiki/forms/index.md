[Back to index](../index.md)

# Forms

Custom Symfony form types and form-type extensions that give consuming projects file/image upload widgets, a
validated Belgium postcode picker, and consistent date/collection/password UX across every project without
per-project form theme work. Everything here is registered in `config/services.php` and applies automatically once
the bundle is loaded — no opt-in per form.

## Where it is used

| Class | Extends / parent | Block prefix | Registered as |
|---|---|---|---|
| `Form\Type\ImageType` | `AbstractType` (default parent `FormType`) | `image` | service `framework.image_type`, tag `form.type` alias `image` |
| `Form\Type\FileType` | `AbstractType` (default parent `FormType`) | `sumoFile` | service `framework.file_type`, tag `form.type` alias `sumoFile` |
| `Form\Type\BelgiumPostCodeType` | `getParent()` → `ChoiceType` | `sumoBelgiumPostCode` | service `framework.file_type`, tag `form.type` alias `sumoBelgiumPostCode` |
| `Form\Extension\BirthdayTypeExtension` | extends `BirthdayType` | — | tag `form.type_extension` |
| `Form\Extension\CollectionTypeExtension` | extends `CollectionType` | — | tag `form.type_extension` |
| `Form\Extension\DateTypeExtension` | extends `DateType` | — | tag `form.type_extension` |
| `Form\Extension\DateTimeTypeExtension` | extends `DateTimeType` | — | tag `form.type_extension` |
| `Form\Extension\TimeTypeExtension` | extends `TimeType` | — | tag `form.type_extension` |
| `Form\Extension\TogglePasswordTypeExtension` | extends `PasswordType` | — | tag `form.type_extension` |

Sub-page: [Belgium postcode](belgium-post-code.md).

## `ImageType` / `FileType` — control flow

Both types share the same shape (`ImageType` for `AbstractImage`, `FileType` for `AbstractFile`, see
`docs/wiki/uploads/`). Source: `src/Form/Type/ImageType.php`, `src/Form/Type/FileType.php`.

1. **`PRE_SET_DATA`** — reads the current model data to decide whether the file is already present
   (`getData()?->getFileName() !== null`). If empty and the field is `required`, a Symfony `FileType` (core) sub-field
   named `file` is added with a `NotBlank` constraint; if a file already exists, the sub-field is added as *not*
   required. This means re-submitting the form without touching the file input never fails required validation once a
   value already exists.
2. A `remove` checkbox sub-field (`property_path: pendingDeletion`) is added conditionally, only when
   `show_remove_file` / `show_remove_image` is true. It maps directly onto `AbstractFile::setPendingDeletion()` /
   `isPendingDeletion()`.
3. **`empty_data`** returns an anonymous class extending `stdClass` with `setFile`/`getFile`/`setPendingDeletion`/
   `getPendingDeletion`. This is the view-data holder the compound form's `file`/`remove` children actually write to
   when there is no existing `AbstractFile`/`AbstractImage` yet — `data_class` is the abstract VO class, which cannot
   be instantiated directly, so this stand-in object fills that role.
4. A **model transformer** (`CallbackTransformer`) reverse-maps that stand-in (or an existing VO) back to a real value
   object on submit: `$fileClass::fromUploadedFile($file->getFile())`, then **returns a clone**. The clone is
   deliberate (see the inline comment in both types) — a new object identity is required for Doctrine's change
   tracking to register the property as changed and run the entity's lifecycle callbacks.
5. **`buildView`** exposes `preview_url` (`AbstractFile::getWebPath()`), forces `show_remove_file`/`show_remove_image`
   to `false` whenever the field is `required` (you can't be allowed to remove a file you must have), and copies
   `preview_label` / `preview_class` into view vars.

Rendered by the `image_widget` / `sumoFile_widget` blocks in `templates/Form/fields.html.twig`, matched by block
prefix — a project overriding these must keep the block name in sync with `getBlockPrefix()`.

## `CollectionTypeExtension`

Source: `src/Form/Extension/CollectionTypeExtension.php`.

Adds to every `CollectionType`: `allow_drag_and_drop` (bool, default `true`), `add_button_label`, and
`minimum_required_items` / `maximum_required_items` (ints, default `0` / `null`).

- `buildForm` validates the two count options at form-build time (throws `InvalidArgumentException` if
  `minimum_required_items < 0` or `maximum_required_items < minimum_required_items`) — this is a developer-facing
  fail-fast, not user validation.
- A `POST_SUBMIT` listener enforces the min/max as real `FormError`s on the collection field, translated through the
  `validators` domain (falls back to the English literal if untranslated).
- `buildView` copies all four options into view vars; they drive `data-min`/`data-max`/`data-allow-drag-and-drop`
  attributes consumed by the `form-collection` Stimulus controller and the `renderCollectionItem` macro in
  `templates/Form/fields.html.twig` (see `docs/forms.md` for how to reuse that macro in a project form theme).

## Date/time family

`DateTypeExtension`, `TimeTypeExtension`, `DateTimeTypeExtension` (`src/Form/Extension/`) only set option defaults
(`format`, `widget: single_text`, `html5: false`, `minimum_date`/`maximum_date`) and copy them into view vars
(`format`, `divider`, `minimum_date`, `maximum_date`, `helper_text`) for the Flatpickr Stimulus controller wired in
`date_widget`/`time_widget`/`datetime_widget` (`templates/Form/fields.html.twig`). No widget/transform logic lives
here — see `docs/using-date-pickers.md` for the option reference.

`BirthdayTypeExtension` only adds one default: `maximum_date` = "now" (prevents future birthdates). Since
`BirthdayType`'s Symfony parent is `DateType`, `DateTypeExtension` already applies to it through the type's parent
chain — `BirthdayTypeExtension` layers on top rather than duplicating the date-picker wiring.

## `TogglePasswordTypeExtension`

Source: `src/Form/Extension/TogglePasswordTypeExtension.php`. Adds a `toggle` bool option (default `true`) plus
label/class options. When `toggle` is enabled, `buildView` splices `'toggle_password'` into `block_prefixes` (picked
up by the `toggle_password_widget` block) and writes `data-controller`/`data-toggle-password-*-value` attributes for
a Stimulus controller. Set `'toggle' => false` per field to opt a specific password field out.

## Edge cases

- **Duplicate service id**: `config/services.php` registers both `FileType` (line ~99) and `BelgiumPostCodeType`
  (line ~102) under the *same* service id, `framework.file_type` — the second `->set()` call replaces the first
  `Definition` in the container outright (Symfony DI just overwrites same-key definitions). This looks like a
  copy-paste artifact rather than intentional sharing. If `FileType` ever appears not to register as a form type,
  check this first before assuming the type itself is broken.
- `FileType`/`ImageType` both declare `'constraints' => [new Valid()]` and `'error_bubbling' => false` — validation on
  the nested `file`/`remove` fields surfaces on the parent field, not the children, when you render `form_errors()`.

## Adding a new type extension

Extend `AbstractTypeExtension`, implement `getExtendedTypes(): iterable` returning the target Symfony type(s),
override `configureOptions()`/`buildView()`/`buildForm()` as needed, then register it in `config/services.php` with
`->tag('form.type_extension', ['extended_type' => TargetType::class])`. No further wiring is needed — Symfony applies
extensions to every instance of the extended type project-wide.
