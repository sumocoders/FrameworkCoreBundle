<?php

namespace SumoCoders\FrameworkCoreBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigureMenuEvent extends Event
{
    public const EVENT_NAME = 'framework_core.configure_menu';

    /**
     * @var \Knp\Menu\FactoryInterface
     */
    private $factory;

    /**
     * @var \Knp\Menu\ItemInterface
     */
    private $menu;

    /**
     * @param FactoryInterface $factory
     * @param ItemInterface    $menu
     */
    public function __construct(FactoryInterface $factory, ItemInterface $menu)
    {
        $this->setFactory($factory);
        $this->setMenu($menu);
    }

    /**
     * @param FactoryInterface $factory
     */
    private function setFactory($factory)
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
     * @param ItemInterface $menu
     */
    private function setMenu($menu)
    {
        $this->menu = $menu;
    }

    /**
     * @return \Knp\Menu\ItemInterface
     */
    public function getMenu()
    {
        return $this->menu;
    }
}
