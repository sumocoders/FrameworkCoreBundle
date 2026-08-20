# Menu

The bundle builds navigation using [KnpMenu](https://symfony.com/bundles/KnpMenuBundle/current/index.html).
`MenuBuilder` dispatches a `ConfigureMenuEvent`, consuming apps listen to this event to add items.

## Prerequisites

`knplabs/knp-menu-bundle` must be installed (included in the application skeleton).

## Usage

Create an event listener in `src/EventListener/`:

```php
<?php

namespace App\EventListener;

use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use SumoCoders\FrameworkCoreBundle\EventListener\DefaultMenuListener;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MenuListener extends DefaultMenuListener implements EventSubscriberInterface
{
    public function onConfigureMenu(ConfigureMenuEvent $event): void
    {
        $factory = $event->getFactory();
        $menu = $event->getMenu();

        if ($this->getSecurity()->isGranted('ROLE_ADMIN')) {
            $menu->addChild(
                $factory->createItem(
                    $this->getTranslator()->trans('Users'),
                    [
                        'route'            => 'user_admin_overview',
                        'labelAttributes'  => [
                            'icon' => 'bi bi-person-fill',
                        ],
                        'extras' => [
                            'routes' => [
                                'user_admin_add',
                                'user_admin_edit',
                            ],
                        ],
                    ],
                )
            );
        }
    }

    public static function getSubscribedEvents(): array
    {
        return [ConfigureMenuEvent::EVENT_NAME => 'onConfigureMenu'];
    }
}
```

Register the listener in `config/services.yaml`:

```yaml
services:
  App\EventListener\MenuListener:
    tags:
      - { name: kernel.event_listener, event: framework_core.configure_menu, method: onConfigureMenu }
```

## DefaultMenuListener helpers

Extending `DefaultMenuListener` gives you three autowired services:

| Method                     | Returns               | Purpose                    |
|----------------------------|-----------------------|----------------------------|
| `$this->getTranslator()`   | `TranslatorInterface` | Translate menu item labels |
| `$this->getSecurity()`     | `Security`            | Check roles/permissions    |
| `$this->getRequestStack()` | `RequestStack`        | Access current request     |

## Active state for child routes

`enableChildRoutes($prefix)` marks a menu item as active when the current route starts with `$prefix`:

```php
$usersItem = $factory->createItem('Users', ['route' => 'user_admin_overview']);
$this->enableChildRoutes($usersItem, 'user_admin_');
$menu->addChild($usersItem);
```

All routes starting with `user_admin_` (e.g. `user_admin_add`, `user_admin_edit`) will mark the item as active.

Alternatively, list specific routes in `extras.routes`:

```php
'extras' => [
    'routes' => ['user_admin_add', 'user_admin_edit'],
],
```

## Icons

The bundle supports Bootstrap Icons (`bi bi-*`) and Font Awesome (`fa-* fa-*`) in `labelAttributes.icon`:

```php
'labelAttributes' => ['icon' => 'bi bi-house-fill'],    // Bootstrap Icons
'labelAttributes' => ['icon' => 'fa-solid fa-house'],   // Font Awesome
```

## Nested items (dropdown)

Create a parent item with `uri => '#'` and add children to it before adding to the root menu:

```php
$paymentsItem = $factory->createItem(
    $this->getTranslator()->trans('Payments'),
    [
        'uri'            => '#',
        'labelAttributes' => ['icon' => 'fa-regular fa-credit-card'],
    ]
);

$paymentsItem->addChild(
    $factory->createItem(
        $this->getTranslator()->trans('Overview'),
        [
            'route'            => 'payments_overview',
            'labelAttributes'  => ['icon' => 'fa-solid fa-money-bill'],
        ]
    )
);

$menu->addChild($paymentsItem);
```

## Troubleshooting

- **Menu item not highlighted**: add the route to `extras.routes` or use `enableChildRoutes` with the correct prefix
- **Item visible to wrong roles**: `isGranted` checks happen at render time; wrap the `addChild` call in a role check
- **Menu not rendering**: verify the listener is registered and tagged with `framework_core.configure_menu`
