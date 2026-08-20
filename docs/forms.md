# Forms

The bundle provides custom form types for images, files, and Belgium postcodes, plus type extensions that enhance the
built-in Symfony form types.

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
use SumoCoders\FrameworkCoreBundle\ValueObject\BelgiumPostCode;
use Symfony\Component\Validator\Constraints\NotBlank;

$builder->add('postcode', BelgiumPostCodeType::class);
```

Add a `BelgiumPostCode` property to your DTO:

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
