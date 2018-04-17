# Using fixtures

Fixtures in the framework are handled by the [DoctrineFixturesBundle](http://symfony.com/doc/current/bundles/DoctrineFixturesBundle/index.html).

## Usage

More information can be found on the page mentioned above. But I will give a 
small example which uses faker, so you have nice data to play with and don't 
need to think that hard.

```php
<?php

namespace SumoCoders\FrameworkUserBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use SumoCoders\FrameworkUserBundle\Entity\User;
use Faker;

class LoadSumoCodersUserData implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $faker = Faker\Factory::create('nl_BE');
        $password = $faker->password(16, 20);

        $sumoCodersAccount = new User();
        $sumoCodersAccount->setUsername($faker->userName);
        $sumoCodersAccount->setPlainPassword($password);
        $sumoCodersAccount->setEmail($faker->email);
        $sumoCodersAccount->setEnabled(true);

        $manager->persist($sumoCodersAccount);
        $manager->flush();

        echo sprintf(
            'The password for the user "%1$s (%2$s)" is %3$s' . PHP_EOL,
            $sumoCodersAccount->getUsername(),
            $sumoCodersAccount->getEmail(),
            $password
        );
    }
}
```
    
Once you wrote your fixtures-classes you can load the fixtures by running:

    app/console doctrine:fixtures:load
    
If you don't want the database to be purged, you should use:

    app/console doctrine:fixtures:load --append

