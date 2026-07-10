<?php

namespace SumoCoders\FrameworkCoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuBuilder
{
    private FactoryInterface $factory;
    private EventDispatcherInterface $eventDispatcher;

    public function __construct(
        FactoryInterface $factory,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->factory = $factory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function createMainMenu(): ItemInterface
    {
        $menu = $this->factory->createItem('root');

        $menu->setChildrenAttribute('class', 'nav navbar-nav');

        $this->eventDispatcher->dispatch(
            new ConfigureMenuEvent(
                $this->factory,
                $menu,
            ),
            ConfigureMenuEvent::EVENT_NAME,
        );

        $this->reorderMenuItems($menu);

        return $menu;
    }

    protected function reorderMenuItems(ItemInterface $menu): void
    {
        $menuOrderArray = [];
        $addLast = [];
        $alreadyTaken = [];

        foreach ($menu->getChildren() as $menuItem) {
            if ($menuItem->hasChildren()) {
                $this->reorderMenuItems($menuItem);
            }

            // @mago-expect analysis:mixed-assignment
            $orderNumber = $menuItem->getExtra('orderNumber');

            // @mago-expect lint:no-else-clause
            if ($orderNumber !== null) {
                // @mago-expect lint:no-else-clause
                // @mago-expect analysis:mixed-argument,impossible-type-comparison,redundant-logical-operation,impossible-null-type-comparison
                // @phpstan-ignore booleanOr.rightAlwaysFalse
                if (!array_key_exists($orderNumber, $menuOrderArray) || is_null($menuOrderArray[$orderNumber])) {
                    $menuOrderArray[$orderNumber] = $menuItem->getName();
                } else {
                    $alreadyTaken[$orderNumber] = $menuItem->getName();
                }
            } else {
                $addLast[] = $menuItem->getName();
            }
        }

        ksort($menuOrderArray);

        if (count($alreadyTaken) > 0) {
            foreach ($alreadyTaken as $key => $value) {
                $keysArray = array_keys($menuOrderArray);
                $position = array_search($key, $keysArray, true);

                if ($position === false) {
                    continue;
                }

                $menuOrderArray = array_merge(
                    array_slice($menuOrderArray, 0, $position),
                    [$value],
                    array_slice($menuOrderArray, $position),
                );
            }
        }

        ksort($menuOrderArray);

        if (count($addLast) > 0) {
            foreach ($addLast as $value) {
                $menuOrderArray[] = $value;
            }
        }

        if (count($menuOrderArray) > 0) {
            $menu->reorderChildren($menuOrderArray);
        }
    }
}
