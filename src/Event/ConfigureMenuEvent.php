<?php

namespace SumoCoders\FrameworkCoreBundle\Event;

use Knp\Menu\FactoryInterface;
use Knp\Menu\ItemInterface;
use Symfony\Contracts\EventDispatcher\Event;

class ConfigureMenuEvent extends Event
{
    public const string EVENT_NAME = 'framework_core.configure_menu';

    public function __construct(
        private FactoryInterface $factory,
        private ItemInterface $menu,
    ) {
    }

    // @mago-expect analysis:unused-method
    // @phpstan-ignore method.unused
    private function setFactory(FactoryInterface $factory): void
    {
        $this->factory = $factory;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    // @mago-expect analysis:unused-method
    // @phpstan-ignore method.unused
    private function setMenu(ItemInterface $menu): void
    {
        $this->menu = $menu;
    }

    public function getMenu(): ItemInterface
    {
        return $this->menu;
    }
}
