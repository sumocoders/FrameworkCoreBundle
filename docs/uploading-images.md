# Uploading images

The bundle provides `AbstractImage` — extends `AbstractFile` with image-specific features: fallback images and web path resolution. Used with `ImageType` for forms and a custom DBAL type for database persistence.

## Prerequisites

- A writable `public/files/` directory in your project
- The entity must have `#[ORM\HasLifecycleCallbacks]`

## Step 1: Create the value object

Create a class that extends `AbstractImage` and implements `getUploadDir()`. Optionally override `FALLBACK_IMAGE` to return a web path for when no image is set.

```php
<?php

namespace App\ValueObject;

use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;

final class UserAvatar extends AbstractImage
{
    public const FALLBACK_IMAGE = '/images/no-avatar.png';

    protected function getUploadDir(): string
    {
        return 'user/avatars';
    }
}
```

Files are stored in `public/files/user/avatars/`. `getWebPath()` returns the fallback image path when no file exists.

## Step 2: Create the DBAL type

```php
<?php

namespace App\DBALType;

use App\ValueObject\UserAvatar;
use SumoCoders\FrameworkCoreBundle\DBALType\AbstractImageType;

final class UserAvatarType extends AbstractImageType
{
    protected function createFromString(string $imageName): UserAvatar
    {
        return UserAvatar::fromString($imageName);
    }

    public function getName(): string
    {
        return 'user_avatar';
    }
}
```

Register it in `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        types:
            user_avatar: App\DBALType\UserAvatarType
```

## Step 3: Add to entity

```php
<?php

namespace App\Entity;

use App\ValueObject\UserAvatar;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
class User
{
    #[ORM\Column(type: 'user_avatar', nullable: true)]
    private ?UserAvatar $avatar = null;

    public function getAvatar(): ?UserAvatar
    {
        return $this->avatar;
    }

    public function setAvatar(?UserAvatar $avatar): void
    {
        $this->avatar = $avatar;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function prepareAvatar(): void
    {
        $this->avatar?->prepareToUpload();
    }

    #[ORM\PostPersist]
    #[ORM\PostUpdate]
    public function uploadAvatar(): void
    {
        $this->avatar?->upload();
    }

    #[ORM\PostRemove]
    public function removeAvatar(): void
    {
        $this->avatar?->remove();
    }
}
```

## Step 4: Add to form

```php
<?php

use App\ValueObject\UserAvatar;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;

$builder->add('avatar', ImageType::class, [
    'image_class'          => UserAvatar::class,
    'label'                => 'forms.labels.avatar',
    'help'                 => 'forms.help.avatar',
    'accept'               => 'image/jpeg,image/png,image/webp',
    'show_preview'         => true,
    'show_remove_image'    => true,
    'remove_image_label'   => 'forms.labels.removeImage',
    'required_image_error' => 'forms.not_blank',
]);
```

See [forms.md](forms.md) for the full `ImageType` options reference.

## Template

```twig
<img src="{{ user.avatar.webPath }}" alt="{{ user.name }}">
```

`getWebPath()` returns the uploaded image URL or `FALLBACK_IMAGE` if no image has been uploaded. Returns an empty string if no fallback is defined and no file exists.

## `AbstractImage` API

Inherits all `AbstractFile` methods plus:

| Method | Description |
|--------|-------------|
| `getWebPath()` | Public image URL, or `FALLBACK_IMAGE` if file missing |
| `getFallbackImage()` | Returns the value of `FALLBACK_IMAGE` constant |

## Notes

- The bundle does not resize or optimize images. Handle resizing in the entity lifecycle callbacks or a post-upload event if needed.
- EXIF data is not stripped. For user-uploaded images consider stripping EXIF in the lifecycle callback before calling `upload()`.

## Troubleshooting

- **Image not uploaded after form submit** — verify the three lifecycle methods (`prepareToUpload`, `upload`, `remove`) are present on the entity
- **Fallback image not showing** — `FALLBACK_IMAGE` must be an absolute public path (e.g. `/images/no-avatar.png`), not a relative path
- **Old image not deleted on replace** — ensure `prepareToUpload()` is called in `PreUpdate`; it stores the old filename for deletion during `upload()`
- **Preview not showing in form** — the `ImageType` calls `getWebPath()` on the current value; check the file exists at the returned path
