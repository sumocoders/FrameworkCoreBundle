# Uploading images

You can find a base value object that you can use to upload images.

It can be used in combination with the form type `SumoCoders\FrameworkCoreBundle\Form\Type\ImageType`

While most of the things you need to do are already written for you, you will still need to add some configuration for
each implementation.

## Basic implementation

### Create a value object

Not all images are created equal. The image you want to upload has a specific meaning in your application and therefor
your implementation should reflect that.

* Create a new class
* Extend the class `SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage`
* Implement the `getUploadDir()` method (for documentation about this see the phpdoc)
* If you want a fallback image you can overwrite the constant `FALLBACK_IMAGE`

After implementing this your value object will be transformed into the web path of your file when it is sent to the
template.

This way you can just use it like `myEntity.myImage`

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\ValueObject;

use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;

final class Avatar extends AbstractImage
{
    const FALLBACK_IMAGE = 'no-avatar.png';

    /**
     * @return string
     */
    protected function getUploadDir()
    {
        return 'user/avatar';
    }
}
```

### Create a DBALType

In order to save the file to the database using doctrine we need a DBALType

* Create a new class
* Extend the class `SumoCoders\FrameworkCoreBundle\DBALType\AbstractImageType`
* Implement the methods `createFromString()` and `getName()`
* Register your DBALType (doctrine.dbal.types)

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\DBALType;

use SumoCoders\FrameworkCoreBundle\DBALType\AbstractImageType;
use SumoCoders\FrameworkUserBundle\ValueObject\Avatar;

final class AvatarType extends AbstractImageType
{
    /**
     * @param string $fileName
     *
     * @return Avatar
     */
    protected function createFromString($imageName)
    {
        return Avatar::fromString($imageName);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'avatar';
    }
}
```

**app/config/config.yml**

```yaml
doctrine:
  dbal:
    types:
      user_avatar_type: SumoCoders\FrameworkUserBundle\DBALType\AvatarType
```

### Update your entity

Now that we have our DBAL type and our value object we can add it to our entity

* Add a property to your class for your value object
* Set the column type to the name your DBAL type is registered on
* Add the `@ORM\HasLifecycleCallbacks` annotation to the entity
* Add the lifecycle callbacks to your entity as described in the phpdoc of the
  `SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage` class

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use SumoCoders\FrameworkUserBundle\ValueObject\Avatar;

/**
 * User
 *
 * @ORM\Table()
 * @ORM\Entity
 * @ORM\HasLifecycleCallbacks()
 */
class User extends BaseUser
{
    /**
     * @var Avatar
     *
     * @ORM\Column(type="user_avatar_type")
     */
    protected $avatar;

    /**
     * @return Avatar
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * @param Avatar $avatar
     * @return self
     */
    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function prepareToUploadAvatar()
    {
        $this->avatar->prepareToUpload();
    }

    /**
     * @ORM\PostUpdate()
     * @ORM\PostPersist()
     */
    public function uploadAvatar()
    {
        $this->avatar->upload();
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeAvatar()
    {
        $this->avatar->remove();
    }
}
```

### Update your form type

For the last step you need to add your file to your form.

* Use `SumoCoders\FrameworkCoreBundle\Form\Type\ImageType` as the form type
* Set the fully qualified class name (FQCN) of your value object in the option `image_class` (tip: you can use
  `MyImage::class` for that)

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use SumoCoders\FrameworkUserBundle\ValueObject\Avatar;
use Symfony\Component\Form\FormBuilderInterface;

class UserType extends RegistrationFormType
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'sumocoders_frameworkuserbundle_user';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('avatar', ImageType::class, ['image_class' => Avatar::class]);
    }
}
```

## Extra configuration options

To make your life even easier, the form FileType has some interesting configuration options on top of the default
options that the Symfony FileType already has.

* `show_preview`: By default we will show a preview of the current image if there is one. You can disable this using
  this option.
* `preview_class`: You can use this option to add an extra class to the preview image, for example you could add
  `img-circle` to make the preview image round
* `show_remove_image`: If your image is not required we will automatically add the option for the user to remove the
  image, You can disable this using this option.
* `remove_image_label`: You can use it to change the translation label of the remove image checkbox.
