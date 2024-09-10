# Uploading files

You can find a base value object that you can use to upload files.

It can be used in combination with the form type `SumoCoders\FrameworkCoreBundle\Form\Type\FileType`

While most of the things you need to do are already written for you, you will still need to add some configuration for
each implementation.

## Basic implementation

### Create a value object

Not all files are created equal. The file you want to upload has a specific meaning in your application and therefor
your implementation should reflect that.

* Create a new class
* Extend the class `SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile`
* Implement the `getUploadDir()` method (for documentation about this see the phpdoc)

After implementing this your value object will be transformed into the web path of your file when it is sent to the
template.

This way you can just use it like `myEntity.myFile`

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\ValueObject;

use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;

final class CV extends AbstractFile
{
    /**
     * @return string
     */
    protected function getUploadDir()
    {
        return 'user/cv';
    }
}
```

### Create a DBALType

In order to save the file to the database using doctrine we need a DBALType

* Create a new class
* Extend the class `SumoCoders\FrameworkCoreBundle\DBALType\AbstractFileType`
* Implement the methods `createFromString()` and `getName()`
* Register your DBALType (doctrine.dbal.types)

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\DBALType;

use SumoCoders\FrameworkCoreBundle\DBALType\AbstractFileType;
use SumoCoders\FrameworkUserBundle\ValueObject\CV;

final class CVType extends AbstractFileType
{
    /**
     * @param string $fileName
     *
     * @return CV
     */
    protected function createFromString($fileName)
    {
        return CV::fromString($fileName);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cv';
    }
}
```

**app/config/config.yml**

```yaml
doctrine:
  dbal:
    types:
      user_cv_type: SumoCoders\FrameworkUserBundle\DBALType\CVType
```

### Update your entity

Now that we have our DBAL type and our value object we can add it to our entity

* Add a property to your class for your value object
* Set the column type to the name your DBAL type is registered on
* Add the `@ORM\HasLifecycleCallbacks` annotation to the entity
* Add the lifecycle callbacks to your entity as described in the phpdoc of the
  `SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile` class

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use SumoCoders\FrameworkUserBundle\ValueObject\CV;

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
     * @var CV
     *
     * @ORM\Column(type="user_cv_type")
     */
    protected $cv;

    /**
     * @return CV
     */
    public function getCv()
    {
        return $this->cv;
    }

    /**
     * @param CV $cv
     * @return self
     */
    public function setCv($cv)
    {
        $this->cv = $cv;

        return $this;
    }

    /**
     * @ORM\PreUpdate()
     * @ORM\PrePersist()
     */
    public function prepareToUploadCV()
    {
        $this->cv->prepareToUpload();
    }

    /**
     * @ORM\PostUpdate()
     * @ORM\PostPersist()
     */
    public function uploadCV()
    {
        $this->cv->upload();
    }

    /**
     * @ORM\PostRemove()
     */
    public function removeCV()
    {
        $this->cv->remove();
    }
}
```

### Update your form type

For the last step you need to add your file to your form.

* Use `SumoCoders\FrameworkCoreBundle\Form\Type\FileType` as the form type
* Set the fully qualified class name (FQCN) of your value object in the option `file_class` (tip: you can use
  `MyFile::class` for that)

#### Example

```php
<?php

namespace SumoCoders\FrameworkUserBundle\Form;

use FOS\UserBundle\Form\Type\RegistrationFormType;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;
use SumoCoders\FrameworkUserBundle\ValueObject\CV;
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

        $builder->add('cv', FileType::class, ['file_class' => CV::class]);
    }
}
```

## Extra configuration options

To make your life even easier, the form FileType has some interesting configuration options on top of the default
options that the Symfony FileType already has.

* `show_preview`: By default we will show a link to view the current file if there is one. You can disable this using
  this option.
* `preview_label`: You can use it to change the translation label that will be in the link to view your current file.
* `show_remove_file`: If your file is not required we will automatically add the option for the user to remove the file,
  You can disable this using this option.
* `remove_file_label`: You can use it to change the translation label of the remove file checkbox.
