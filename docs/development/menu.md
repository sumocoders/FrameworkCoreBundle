# Adding items into the menu/navigation

To create a menu & add items to it, you'll need to set up an event listener that listens to the
`framework_core.configure_menu` event.

In short, you'll need to add the following:

* In src/EventListener, create a file called MenuListener just like the example below.
* In config/services.yaml, add the following configuration snippet:

```yml
services:
  App\EventListener\MenuListener:
    tags:
      - { name: kernel.event_listener, event: framework_core.configure_menu, method: onConfigureMenu }
```

To make things easier, there's a DefaultMenuListener to extend your MenuListener from. This base class already has three
autowired arguments:

* TranslatorInterface
* Security
* RequestStack

You can use them like so:

* `$this->getTranslator()->trans('some text')` to translate stuff
* `$this->getSecurity()->isGranted('ROLE_ADMIN');` to check for roles
* `$this->getRequestStack()->getCurrentRequest()->...` to access the current request.

There is also a helper called `enableChildRoutes`, which takes a prefix string as an argument. Calling this method on a
menu item, will activate it when a route is visited that starts with the prefix you pass.

In short, if you have a menu item with `user_overview` as the route, and you enable child routes with the `user_`
prefix, all the following routes will also mark the user menu item as active:

* `user_create`
* `user_update`
* `user_export`
* `user_whatever`

## The example listener

```php
<?php

namespace App\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
        
        $userItem = $factory->createItem(
            $this->getTranslator()->trans('menu.something_regular'),
            [
                'route' => 'user_overview',
                'labelAttributes' => [
                    'icon' => 'fas fa-user',
                ],
            ],
        );
        
        $userMenuItem->enableChildRoutes($userItem, 'user_');
        
        $menu->addChild($userMenuItem);
    }
}
