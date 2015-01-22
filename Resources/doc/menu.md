# Adding items into the menu/navigation

Because we want a modular system we are using events to build the menu. When
the menu is build an event called "framework_core.configure_menu" is triggered.

Your bundle can subscribe on this event. Follow the steps below to implement it
in your own bundle

## Create a listener

```php
<?php

namespace SumoCoders\FrameworkExampleBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;

class MenuListener extends DefaultMenuListener
{
    public function onConfigureMenu(ConfigureMenuEvent $event)
    {
        $menu = $event->getMenu();
        $menuItem = $event->getFactory()->createItem(
            'menu.example.index',
            array(
                'uri' => '#',
            )
        );
        $menuItem->setAttribute('id', 'example');
        $menuItem->setAttribute('icon', 'iconExample');
        $menuItem->setChildrenAttribute('class', 'subNavigation');
        $menuItem->setLinkAttribute('class', 'toggleSubNavigation');
        $menuItem->setExtra('orderNumber', 2);

        $firstChild = $event->getFactory()->createItem(
            'menu.example.first',
            array(
                'uri' => '#',
            )
        );
        $firstChild->setExtra('orderNumber', 1);
        $menuItem->addChild($firstChild);
    }
}
```

## Listen on the event

Add the configuration

```yml
services:
  framework_example.listener.menu_listener:
    class: SumoCoders\FrameworkExampleBundle\EventListener\MenuListener
    calls:
      - [setSecurityContext, ["@security.context"]]
    tags:
      - { name: kernel.event_listener, event: framework_core.configure_menu, method: onConfigureMenu }
```

## Some guidelines

You can use normal numbers such as 1, 2, 3. But I would advice that you use
hundreds, as it will allow you to insert items in between in a later stage.
