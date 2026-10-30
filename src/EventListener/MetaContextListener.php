<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use ReflectionClass;
use SumoCoders\FrameworkCoreBundle\Attribute\HasMeta;
use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Service\MetaContext;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

final readonly class MetaContextListener
{
    public function __construct(
        private MetaContext $metaContext,
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = $event->getRequest()->attributes->getString('_route');
        if (str_starts_with($route, '_')) {
            return;
        }

        // @mago-expect analysis:mixed-assignment
        foreach ($event->getNamedArguments() as $name => $argument) {
            if (!is_object($argument)) {
                continue;
            }

            $reflection = new ReflectionClass($argument);
            $attributes = $reflection->getAttributes(HasMeta::class);

            if (count($attributes) === 0) {
                continue;
            }

            $hasMeta = $attributes[0]->newInstance();
            // @mago-expect analysis:string-member-selector,mixed-assignment
            $meta = $argument->{$hasMeta->meta}();
            if (!$meta instanceof Meta) {
                return;
            }

            $routeParam = $event->getRequest()->attributes->getString($name);
            // @mago-expect analysis:string-member-selector,mixed-assignment
            $slug = $argument->{$hasMeta->slug}();
            if ($routeParam === $slug) {
                $this->metaContext->setMeta($meta);
            }

            return;
        }
    }
}
