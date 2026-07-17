# Uploading files

The bundle provides `AbstractFile`, a value object that handles file storage, naming, and lifecycle hooks for Doctrine
entities. Used with `FileType` for forms and a custom DBAL type for database persistence.

## Prerequisites

- A writable `public/files/` directory in your project
- The entity must have `#[ORM\HasLifecycleCallbacks]`

## Step 1: Create the value object

Create a class that extends `AbstractFile` and implements `getUploadDir()`. The upload directory is relative to
`public/files/`.

```php
<?php

namespace App\ValueObject;

use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;

final class UserDocument extends AbstractFile
{
    protected function getUploadDir(): string
    {
        return 'user/documents';
    }
}
```

Files are stored in `public/files/user/documents/` and served from `/files/user/documents/<filename>`.

## Step 2: Create the DBAL type

```php
<?php

namespace App\DBALType;

use App\ValueObject\UserDocument;
use SumoCoders\FrameworkCoreBundle\DBALType\AbstractFileType;

final class UserDocumentType extends AbstractFileType
{
    protected function createFromString(string $fileName): UserDocument
    {
        return UserDocument::fromString($fileName);
    }

    public function getName(): string
    {
        return 'user_document';
    }
}
```

Register it in `config/packages/doctrine.yaml`:

```yaml
doctrine:
  dbal:
    types:
      user_document: App\DBALType\UserDocumentType
```

## Step 3: Add to entity

```php
<?php

namespace App\Entity;

use App\ValueObject\UserDocument;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Column(type: 'user_document', nullable: true)]
    private ?UserDocument $document = null;

    public function getDocument(): ?UserDocument
    {
        return $this->document;
    }

    public function setDocument(?UserDocument $document): void
    {
        $this->document = $document;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function prepareDocument(): void
    {
        $this->document?->prepareToUpload();
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function uploadDocument(): void
    {
        $this->document?->upload();
    }

    #[ORM\PostRemove]
    public function removeDocument(): void
    {
        $this->document?->remove();
    }
}
```

## Step 4: Add to form

```php
<?php

use App\ValueObject\UserDocument;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;

$builder->add('document', FileType::class, [
    'file_class'          => UserDocument::class,
    'label'               => 'forms.labels.document',
    'help'                => 'forms.help.document',
    'accept'              => 'application/pdf',
    'show_preview'        => true,
    'preview_label'       => 'forms.labels.viewCurrentFile',
    'show_remove_file'    => true,
    'remove_file_label'   => 'forms.labels.removeFile',
    'required_file_error' => 'forms.not_blank',
]);
```

See [forms.md](forms.md) for the full `FileType` options reference.

## Template

```twig
{% if user.document %}
    <a href="{{ user.document }}">Download document</a>
{% endif %}
```

`AbstractFile` implements `__toString()` returning the web path (`/files/user/documents/<filename>`), or an empty string
if no file exists.

## `AbstractFile` API

| Method                  | Description                                      |
|-------------------------|--------------------------------------------------|
| `getFileName()`         | Raw stored filename                              |
| `getWebPath()`          | Public URL path, or empty string if file missing |
| `getAbsolutePath()`     | Absolute filesystem path                         |
| `hasFile()`             | Whether a new `UploadedFile` is pending          |
| `markForDeletion()`     | Schedules the file for removal on next flush     |
| `setNamePrefix(string)` | Prepends a slug to the generated filename        |

## Troubleshooting

- **File not uploaded after form submit**: verify the three lifecycle methods (`prepareToUpload`, `upload`, `remove`)
  are present on the entity with the correct `#[ORM\*]` attributes
- **`public/files/` directory missing**: create it and ensure it is web-accessible; check your web server configuration
- **Old file not deleted on replace**: the `upload()` method deletes the old file; this only works if
  `prepareToUpload()` was called first in `PreUpdate`
- **Form always shows file as required**: `FileType` uses `required` based on whether the value object has an existing
  file; pass `'required' => false` to disable the constraint
