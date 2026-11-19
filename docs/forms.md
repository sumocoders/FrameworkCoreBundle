# Forms

The bundle provides custom form types for images, files, and Belgium postcodes, plus type extensions that enhance the
built-in Symfony form types.

---

## Declaring fields with attributes

A form does not need its own `AbstractType` class. Put `#[AsFormType]` on the class that holds the data
and `#[FormField]` on each property that should become a field; the class name is then usable wherever a
form type name is expected.

```php
<?php

namespace App\Dto;

use App\ValueObject\UserPhoto;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Validator\Constraints\NotBlank;

#[AsFormType]
class ProfileData
{
    // no type given: it is guessed from the property type, as with $builder->add()
    #[FormField]
    #[NotBlank]
    public string $name = '';

    #[FormField(EmailType::class)]
    public ?string $email = null;

    #[FormField(ImageType::class, ['image_class' => UserPhoto::class])]
    public ?UserPhoto $photo = null;
}
```

```php
$form = $this->createForm(ProfileData::class, $profileData);
```

Validation stays where it already is: constraints are read from the validator attributes on the same
properties, not from `#[FormField]`.

**Use a form class instead when** the form needs form events, a data transformer, a field that only
exists at runtime, or an option whose value is a service or a closure. Option values on `#[FormField]`
are compiled into the container, so they must be scalars, arrays, enums, `null`, or a
`Symfony\Component\DependencyInjection\Reference`. Anything else fails with *"only scalar, array, enum
and service reference values can be compiled"*. To add that logic to an attribute-declared form without
giving up the attributes, write an `AbstractTypeExtension` whose `getExtendedTypes()` yields the data
class.

Two things to know about registration:

- The class is picked up through container autoconfiguration, so it has to be inside the `src/` scan.
  The default `config/services.yaml` excludes `src/Entity/`, so entities are not registered unless you
  add them yourself. DTOs under `src/Dto/` or `src/DataTransferObject/` work out of the box.
- Mistakes — a duplicate field name, an unknown form type, `data_class` set on `#[AsFormType]` — are
  reported when the container compiles, not on the first request.

Each custom type and extension below is shown both ways. The options are identical in both styles.

---

## Custom form types

### ImageType

Renders a file input for an `AbstractImage` subclass with an optional preview and a remove checkbox. The form field
expects the entity property to be typed as your `AbstractImage` subclass.

See [uploading-images.md](uploading-images.md) for creating the required `AbstractImage` subclass.

```php
<?php

use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;

$builder->add('photo', ImageType::class, [
    'image_class'           => UserPhoto::class,
    'label'                 => 'forms.labels.photo',
    'help'                  => 'forms.help.photo',
    'accept'                => 'image/jpeg,image/png,image/webp',
    'show_preview'          => true,
    'show_remove_image'     => true,
    'remove_image_label'    => 'forms.labels.removeImage',
    'required_image_error'  => 'forms.not_blank',
]);
```

Or, on a DTO:

```php
<?php

use App\ValueObject\UserPhoto;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use Symfony\Component\Form\Attribute\FormField;

#[FormField(ImageType::class, [
    'image_class'          => UserPhoto::class,
    'label'                => 'forms.labels.photo',
    'help'                 => 'forms.help.photo',
    'accept'               => 'image/jpeg,image/png,image/webp',
    'show_preview'         => true,
    'show_remove_image'    => true,
    'remove_image_label'   => 'forms.labels.removeImage',
    'required_image_error' => 'forms.not_blank',
])]
public ?UserPhoto $photo = null;
```

**Options:**

| Option                 | Type     | Default                      | Required | Description                                         |
|------------------------|----------|------------------------------|----------|-----------------------------------------------------|
| `image_class`          | `string` |                              | yes      | FQCN of your `AbstractImage` subclass               |
| `label`                | `string` |                              | no       | Form field label (standard Symfony form option)     |
| `help`                 | `string` |                              | yes      | Help text below the field                           |
| `accept`               | `string` | `'image/*'`                  | yes      | Accepted MIME types for the file input              |
| `show_preview`         | `bool`   | `true`                       | yes      | Show current image as a preview                     |
| `show_remove_image`    | `bool`   | `true`                       | yes      | Show a checkbox to delete the current image         |
| `remove_image_label`   | `string` | `'forms.labels.removeImage'` | yes      | Label for the remove checkbox                       |
| `required_image_error` | `string` | `'forms.not_blank'`          | yes      | Validation message when a required image is missing |

---

### FileType

Same as `ImageType` but for `AbstractFile` subclasses (any file type).

See [uploading-files.md](uploading-files.md) for creating the required `AbstractFile` subclass.

```php
<?php

use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;

$builder->add('document', FileType::class, [
    'file_class'         => UserDocument::class,
    'label'              => 'forms.labels.document',
    'help'               => 'forms.help.document',
    'accept'             => 'application/pdf',
    'show_preview'       => true,
    'preview_label'      => 'forms.labels.viewCurrentFile',
    'show_remove_file'   => true,
    'remove_file_label'  => 'forms.labels.removeFile',
    'required_file_error' => 'forms.not_blank',
]);
```

Or, on a DTO:

```php
<?php

use App\ValueObject\UserDocument;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;
use Symfony\Component\Form\Attribute\FormField;

#[FormField(FileType::class, [
    'file_class'          => UserDocument::class,
    'label'               => 'forms.labels.document',
    'help'                => 'forms.help.document',
    'accept'              => 'application/pdf',
    'show_preview'        => true,
    'preview_label'       => 'forms.labels.viewCurrentFile',
    'show_remove_file'    => true,
    'remove_file_label'   => 'forms.labels.removeFile',
    'required_file_error' => 'forms.not_blank',
])]
public ?UserDocument $document = null;
```

**Options:**

| Option                | Type           | Default                          | Required | Description                                           |
|-----------------------|----------------|----------------------------------|----------|-------------------------------------------------------|
| `file_class`          | `string`       |                                  | yes      | FQCN of your `AbstractFile` subclass                  |
| `label`               | `string`       |                                  | no       | Form field label (standard Symfony form option)       |
| `help`                | `string`       |                                  | yes      | Help text below the field                             |
| `accept`              | `string\|null` | `null`                           | yes      | Accepted MIME types for the file input (`null` = any) |
| `show_preview`        | `bool`         | `true`                           | yes      | Show a link to the current file                       |
| `preview_label`       | `string`       | `'forms.labels.viewCurrentFile'` | yes      | Link text for the current file preview                |
| `show_remove_file`    | `bool`         | `true`                           | yes      | Show a checkbox to delete the current file            |
| `remove_file_label`   | `string`       | `'forms.labels.removeFile'`      | yes      | Label for the remove checkbox                         |
| `required_file_error` | `string`       | `'forms.not_blank'`              | yes      | Validation message when a required file is missing    |

---

### BelgiumPostCodeType

A select field restricted to valid Belgian postcodes. Internally uses a predefined list of all Belgian postcodes.

```php
<?php

use SumoCoders\FrameworkCoreBundle\Form\Type\BelgiumPostCodeType;

$builder->add('postcode', BelgiumPostCodeType::class);
```

Either way, the DTO needs a `BelgiumPostCode` property:

```php
<?php

use SumoCoders\FrameworkCoreBundle\ValueObject\BelgiumPostCode;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddressData
{
    #[NotBlank]
    public ?BelgiumPostCode $postcode = null;
}
```

Or declare the field on that same DTO and drop the form class:

```php
<?php

use SumoCoders\FrameworkCoreBundle\Form\Type\BelgiumPostCodeType;
use SumoCoders\FrameworkCoreBundle\ValueObject\BelgiumPostCode;
use Symfony\Component\Form\Attribute\AsFormType;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Validator\Constraints\NotBlank;

#[AsFormType]
class AddressData
{
    #[FormField(BelgiumPostCodeType::class)]
    #[NotBlank]
    public ?BelgiumPostCode $postcode = null;
}
```

---

## Type extensions

These extensions apply automatically to the listed Symfony form types. No explicit registration needed.

### Date, Time, DateTime, Birthday

Activates [Flatpickr](https://flatpickr.js.org/) on `DateType`, `TimeType`, `DateTimeType`, and `BirthdayType`. The
`widget` defaults to `single_text` and `html5` defaults to `false`.

See [using-date-pickers.md](using-date-pickers.md) for options and examples.

### CollectionType

Adds drag-and-drop reordering and minimum/maximum item count validation to `CollectionType`.

**Added options:**

| Option                   | Type        | Default                   | Description                                          |
|--------------------------|-------------|---------------------------|------------------------------------------------------|
| `allow_drag_and_drop`    | `bool`      | `true`                    | Enable drag-and-drop row reordering                  |
| `add_button_label`       | `string`    | `'forms.buttons.addItem'` | Translation key for the "Add item" button            |
| `minimum_required_items` | `int`       | `0`                       | Minimum number of items required                     |
| `maximum_required_items` | `int\|null` | `null`                    | Maximum number of items allowed (`null` = unlimited) |

```php
$builder->add('contacts', CollectionType::class, [
    'entry_type'            => ContactType::class,
    'allow_add'             => true,
    'allow_delete'          => true,
    'minimum_required_items' => 1,
    'maximum_required_items' => 5,
]);
```

Or, on a DTO:

```php
<?php

use App\Dto\ContactData;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

#[FormField(CollectionType::class, [
    'entry_type'             => ContactData::class,
    'allow_add'              => true,
    'allow_delete'           => true,
    'minimum_required_items' => 1,
    'maximum_required_items' => 5,
])]
public array $contacts = [];
```

`entry_type` takes any form type name, so it accepts an `#[AsFormType]` DTO class (`ContactData` above)
just as well as a form class (`ContactType`).

#### Reusing the row markup in your own form theme

Each collection row is rendered by the `renderCollectionItem` macro in
`templates/Form/fields.html.twig`. If your project overrides the `collection_widget` or
`collection_rows` block, import the macro and call it so your rows keep the same markup,
drag handle, and delete button:

```twig
{% block collection_rows %}
  {% import '@SumoCodersFrameworkCore/Form/fields.html.twig' as formMacros %}

  <ul data-role="collection-item-container" class="list-unstyled" data-form-collection-target="itemContainer">
    {% for child in form %}
      {{ formMacros.renderCollectionItem(child, allow_delete, allow_drag_and_drop) }}
    {% endfor %}
  </ul>
{% endblock %}
```

The signature is `renderCollectionItem(item, allow_delete = false, allow_drag_and_drop = false)`.
Only `item` is required; omitting either boolean renders the row without that control. Import
the macro inside the block that uses it, not at the root of your theme: form theme blocks run
in the context of the template being rendered, so a root-level import is out of scope there.

### PasswordType

Adds a show/hide toggle button to `PasswordType` fields.

**Added options:**

| Option                     | Type           | Default                         | Description                            |
|----------------------------|----------------|---------------------------------|----------------------------------------|
| `toggle`                   | `bool`         | `true`                          | Enable the show/hide toggle            |
| `hidden_label`             | `string\|null` | `'Hide password'`               | Tooltip/label when password is visible |
| `visible_label`            | `string\|null` | `'Show password'`               | Tooltip/label when password is hidden  |
| `button_classes`           | `string[]`     | `['toggle-password-button']`    | CSS classes on the toggle button       |
| `toggle_container_classes` | `string[]`     | `['toggle-password-container']` | CSS classes on the wrapper element     |

To disable the toggle on a specific field:

```php
$builder->add('apiKey', PasswordType::class, [
    'toggle' => false,
]);
```

Or, on a DTO:

```php
<?php

use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;

#[FormField(PasswordType::class, ['toggle' => false])]
public ?string $apiKey = null;
```

---

## Translations

Form field labels are translated automatically using the form's translation domain. The label key is derived from the
field name (e.g. field `username` → key `Username`).

To provide a custom translation, add a key to your translation file:

```yaml
# translations/messages+intl-icu.en.yaml
Username: 'Enter your username'
```

No change to the form builder is needed.

---

## Internals

### Attribute-declared forms and this bundle

- The type extensions in `src/Form/Extension/` target the built-in Symfony types, not a declaration
  style, so they apply unchanged to a field declared with `#[FormField(DateType::class)]`. Flatpickr,
  the password toggle, and the collection UI behave the same in both styles.
- Every option the bundle's own types accept is a scalar, a string FQCN, or an array, so all of them
  survive the compile-time check that rejects closures and other non-compilable option values.
- `data_class` cannot be set in `#[AsFormType(options: [...])]` — the annotated class *is* the data
  class, and passing it throws while the container compiles.
- Abstract classes carrying `#[AsFormType]` are registered too, specifically so concrete subclasses can
  inherit their fields. A subclass picks up the fields of the closest ancestor that also has
  `#[AsFormType]`; without such an ancestor the parent type is `FormType`. See
  [crud.md](crud.md#without-a-form-class) for how that is used for create/update message classes.

### `ImageType` / `FileType` — control flow

Both types share the same internal shape (`ImageType` for `AbstractImage`, `FileType` for `AbstractFile`):

1. **`PRE_SET_DATA`** adds a core Symfony `file` sub-field. A `NotBlank` constraint is only attached to it when no
   file/image exists yet **and** the field is `required` — so resubmitting a form without touching the file input
   never fails validation once a value already exists.
2. A `remove` checkbox sub-field is added only when `show_remove_file` / `show_remove_image` is `true`, mapped to
   the value object's `pendingDeletion` property.
3. **`empty_data`** returns an anonymous class extending `stdClass` (with `setFile`/`getFile`/`setPendingDeletion`/
   `getPendingDeletion`) as a stand-in view-data holder. This exists because the real `data_class` is the abstract
   value-object class, which can't be instantiated directly.
4. On submit, a `CallbackTransformer` calls `$fileClass::fromUploadedFile(...)` to build the real value object, and
   **deliberately returns a clone** of it rather than the original instance — this gives Doctrine's change tracking
   a new object identity to detect, which is what triggers the property being marked dirty and the entity's
   lifecycle callbacks running.

### `BelgiumPostCodeType` — data source caveats

- The postcode list is always read from the **`nl` locale** resource bundle, regardless of the application's
  current request locale. There is no French or German label variant, even in a multi-locale app.
- `choice_translation_domain` is forced to `false`, so the `"<postcode> - <municipality>"` labels are never passed
  through the translator, in any locale.
- The source data includes non-geographic "postcodes" — embassies, parliamentary chambers, and similar large
  recipients — alongside real municipalities. Treat the list as "valid Belgian postal codes", not strictly
  "municipalities".
- Resolving the type's options throws a `LogicException` if `symfony/intl` isn't installed. If you see that
  exception when using `BelgiumPostCodeType`, add `symfony/intl` as a dependency rather than looking for a
  configuration mistake.
