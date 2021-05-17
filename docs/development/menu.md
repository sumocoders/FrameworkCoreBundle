# Adding items into the menu/navigation

To create a menu & add items to it, you'll need to set up an event listener that listens to the `framework_core.configure_menu` event.

In short, you'll need to add the following:
* In src/EventListener, create a file called MenuListener just like the example below.
* In config/services.yaml, add the following configuration snippet:
```yml
services:
  App\EventListener\MenuListener:
    tags:
      - { name: kernel.event_listener, event: framework_core.configure_menu, method: onConfigureMenu }
```

To make things easier, there's a DefaultMenuListener to extend your MenuListener from. This base class already has two autowired arguments:
 * TranslatorInterface
 * Security

You can use them like so:
* `$this->getTranslator()->trans('some text')` to translate stuff
* `$this->getSecurity()->isGranted('ROLE_ADMIN');` to check for roles


## The example listener

```php
<?php

namespace App\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class MenuListener extends DefaultMenuListener
{
    public function onConfigureMenu(ConfigureMenuEvent $event): void
    {
        $factory = $event->getFactory();
        $menu = $event->getMenu();
        
        if ($this->getSecurity()->isGranted("ROLE_ADMIN")) {
            $menu->addChild(
                $factory->createItem(
                    $this->getTranslator()->trans('menu.something_for_admins'),
                    [
                        'route' => 'route_for_admins',
                        'labelAttributes' => [
                            'icon' => 'fas fa-lock',
                        ],
                    ],
                )
            );
        }
        
        $menu->addChild(
            $factory->createItem(
                $this->getTranslator()->trans('menu.something_regular'),
                [
                    'route' => 'route_for_normal_users',
                    'labelAttributes' => [
                        'icon' => 'fas fa-user',
                    ],
                ],
            )
        );
    }
}