<?php

namespace SumoCoders\FrameworkCoreBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigureMenuEvent extends Event
{
    public const EVENT_NAME = 'framework_core.configure_menu';


    public function __construct(
        private FactoryInterface $factory,
        private ItemInterface $menu,
    ) {
    }

    private function setFactory(FactoryInterface $factory): void
    {
        $this->factory = $factory;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    private function setMenu(ItemInterface $menu)
    {
        $this->menu = $menu;
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }
}
