<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use SumoCoders\FrameworkCoreBundle\Annotation\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb as BreadcrumbValueObject;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouterInterface;

class BreadcrumbListener
{
    private RouterInterface $router;
    private Reader $reader;
    private BreadcrumbTrail $breadcrumbTrail;

    public function __construct(
        RouterInterface $router,
        Reader $reader,
        BreadcrumbTrail $breadcrumbTrail
    ) {
        $this->router = $router;
        $this->reader = $reader;
        $this->breadcrumbTrail = $breadcrumbTrail;
    }

    public function onKernelController(KernelEvent $event): void
    {
        if (!is_array($controller = $event->getController())) {
            return;
        }

        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
            $this->breadcrumbTrail->reset();
        }

        $this->processAnnotations($event, $controller);
    }

    private function processAnnotations(KernelEvent $event, array $controller): void
    {
        $class = new \ReflectionClass($controller[0]);
        if ($class->isAbstract()) {
            throw new \InvalidArgumentException(
                sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class)
            );
        }

        $method = $class->getMethod($controller[1]);
        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
            $this->addBreadcrumbsFromClass($event, $this->reader->getClassAnnotations($class));
            $this->addBreadcrumbsFromMethod($event, $this->reader->getMethodAnnotations($method));
        }
    }

    private function processParentAnnotations(
        KernelEvent $event,
        array $controller,
        ?string $routeName = null,
        ?array $parameters = null
    ): void {
        $class = new \ReflectionClass($controller[0]);
        $method = $class->getMethod($controller[1]);

        if ($event->getRequestType() == HttpKernelInterface::MASTER_REQUEST) {
            $this->addBreadcrumbsFromMethod(
                $event,
                $this->reader->getMethodAnnotations($method),
                $routeName,
                $parameters
            );
        }
    }


    private function addBreadcrumbsFromMethod(
        KernelEvent $event,
        array $annotations,
        ?string $routeName = null,
        ?array $parameters = null
    ): void {
        $this->addBreadcrumbsFromAnnotations($event, $annotations, $routeName, $parameters);
    }

    private function addBreadcrumbsFromClass(KernelEvent $event, array $annotations): void
    {
        $this->addBreadcrumbsFromAnnotations($event, $annotations);
    }

    private function addBreadcrumbsFromAnnotations(
        KernelEvent $event,
        array $annotations,
        ?string $routeName = null,
        ?array $parameters = null
    ) {
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Breadcrumb) {
                if ($annotation->getParentRouteName()) {
                    $controller =  $this->getControllerFromName(
                        $annotation->getParentRouteName()
                    );

                    $requiredParameters = $this->getRouteParametersFromName(
                        $annotation->getParentRouteName()
                    );

                    $parentParameters = [];
                    foreach ($requiredParameters as $requiredParameter) {
                        $parentParameters[$requiredParameter] =
                            $event->getRequest()->attributes->all()[$requiredParameter] ?? '';
                    }

                    if (count($annotation->getParentRouteParameters()) > 0) {
                        $parentParameters = $annotation->getParentRouteParameters();
                    }

                    $this->processParentAnnotations(
                        $event,
                        $controller,
                        $annotation->getParentRouteName(),
                        $parentParameters
                    );
                }
                $title = $annotation->getTitle();

                $url = null;
                if ($routeName !== '' && $routeName !== null) {
                    $url = $this->router->generate(
                        $routeName ?? $annotation->getRouteName(),
                        $parameters ?? $annotation->getRouteParameters()
                    );
                }
                $this->breadcrumbTrail->add(
                    new BreadcrumbValueObject(
                        $title,
                        $url
                    )
                );
            }
        }
    }

    private function getControllerFromName(string $name): array
    {
        $routes = $this->router->getRouteCollection()->all();

        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->getDefault('_canonical_route') !== $name) {
                continue;
            }

            $controller = explode('::', $route->getDefault('_controller'));

            return [
                $controller[0],
                $controller[1] ?? '__invoke',
            ];
        }
    }

    public function getRouteParametersFromName(string $name): array
    {
        $routes = $this->router->getRouteCollection()->all();

        /** @var Route $route */
        foreach ($routes as $route) {
            if ($route->getDefault('_canonical_route') !== $name) {
                continue;
            }

            $compiledRoute = $route->compile();
            if (!$compiledRoute instanceof CompiledRoute) {
                return [];
            }

            return $compiledRoute->getVariables();
        }
    }
}
