<?php

namespace SumoCoders\FrameworkCoreBundle\Menu;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use SumoCoders\FrameworkCoreBundle\Event\ConfigureMenuEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MenuBuilder
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher($eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Symfony\Component\EventDispatcher\EventDispatcherInterface
     */
    public function getEventDispatcher()
    {
        return $this->eventDispatcher;
    }

    /**
     * @param \Knp\Menu\FactoryInterface $factory
     */
    public function setFactory($factory)
    {
        $this->factory = $factory;
    }

    /**
     * @return \Knp\Menu\FactoryInterface
     */
    public function getFactory()
    {
        return $this->factory;
    }

    /**
     * @return ItemInterface
     */
    public function createMainMenu()
    {
        $menu = $this->factory->createItem('root');
        $menu->setChildrenAttribute('class', 'nav');

        $this->eventDispatcher->dispatch(
            ConfigureMenuEvent::EVENT_NAME,
            new ConfigureMenuEvent(
                $this->getFactory(),
                $menu
            )
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
        $menuOrderArray = array();
        $addLast = array();
        $alreadyTaken = array();

        foreach ($menu->getChildren() as $menuItem) {
            if ($menuItem->hasChildren()) {
                $this->reorderMenuItems($menuItem);
            }

            $orderNumber = $menuItem->getExtra('orderNumber');

            if ($orderNumber != null) {
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
                    array($value),
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
