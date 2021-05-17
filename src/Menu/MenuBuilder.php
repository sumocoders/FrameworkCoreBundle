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
        EventDispatcherInterface $eventDispatcher
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
                $menu
            ),
            ConfigureMenuEvent::EVENT_NAME
        );

        $this->reorderMenuItems($menu);

        return $menu;
    }

    /**
     * Reorderd the items in the menu based on the extra data
     *
     * @param ItemInterface $menu
     */
    protected function reorderMenuItems(ItemInterface $menu)
    {
        $menuOrderArray = [];
        $addLast = [];
        $alreadyTaken = [];

        foreach ($menu->getChildren() as $menuItem) {
            if ($menuItem->hasChildren()) {
                $this->reorderMenuItems($menuItem);
            }

            $orderNumber = $menuItem->getExtra('orderNumber');

            if ($orderNumber !== null) {
                if (!isset($menuOrderArray[$orderNumber])) {
                    $menuOrderArray[$orderNumber] = $menuItem->getName();
                } else {
                    $alreadyTaken[$orderNumber] = $menuItem->getName();
                }
            } else {
                $addLast[] = $menuItem->getName();
            }
        }

        ksort($menuOrderArray);

        if (!empty($alreadyTaken)) {
            foreach ($alreadyTaken as $key => $value) {
                $keysArray = array_keys($menuOrderArray);
                $position = array_search($key, $keysArray);

                if ($position === false) {
                    continue;
                }

                $menuOrderArray = array_merge(
                    array_slice($menuOrderArray, 0, $position),
                    [$value],
                    array_slice($menuOrderArray, $position)
                );
            }
        }

        ksort($menuOrderArray);

        if (!empty($addLast)) {
            foreach ($addLast as $value) {
                $menuOrderArray[] = $value;
            }
        }

        if (!empty($menuOrderArray)) {
            $menu->reorderChildren($menuOrderArray);
        }
    }
}
