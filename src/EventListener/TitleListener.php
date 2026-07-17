<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use ReflectionMethod;
use SumoCoders\FrameworkCoreBundle\Attribute\Title;
use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class TitleListener
{
    public function __construct(
        private PageTitle $pageTitleService,
        private Fallbacks $fallbacks,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        private PropertyAccessorInterface $propertyAccess,
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        $method = $this->resolveMethod($event->getController());
        if ($method === null) {
            return;
        }

        $attributes = $this->getTitleAttributes($method);
        if (empty($attributes)) {
            return;
        }

        $parameters = $event->getNamedArguments();

        foreach ($attributes as $attribute) {
            $titleAttribute = $attribute->newInstance();

            if (!$titleAttribute->isExtend()) {
                $this->pageTitleService->setTitle($titleAttribute->getTitle());
                return;
            }

            $title = $this->processTitle($titleAttribute->getTitle(), $parameters);

            if ($titleAttribute->hasParent()) {
                $title .= $this->getTitleFromParent($titleAttribute->getParent(), $parameters);
            }

            $this->pageTitleService->setTitle($title . ' - ' . $this->fallbacks->get('site_title'));
        }
    }

    private function resolveMethod(mixed $controller): ?ReflectionMethod
    {
        if (is_array($controller)) {
            return new ReflectionMethod($controller[0], $controller[1]);
        }

        if (is_object($controller) && !$controller instanceof \Closure) {
            return new ReflectionMethod($controller, '__invoke');
        }

        return null;
    }

    /** @return array<\ReflectionAttribute<Title>> */
    private function getTitleAttributes(ReflectionMethod $method): array
    {
        $attributes = $method->getAttributes(Title::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($attributes !== []) {
            return $attributes;
        }

        return $method->getDeclaringClass()->getAttributes(Title::class, \ReflectionAttribute::IS_INSTANCEOF);
    }

    /**
     * Get the title from the parent route.
     *
     * @param array<mixed> $parameters
     */
    private function getTitleFromParent(Route $parent, array $parameters = []): string
    {
        // Get the route information and the method of the controller
        $routeInformation = $this->getRouteInformation($parent->getName());
        $class = new \ReflectionClass($routeInformation['controller']);
        $method = $class->getMethod($routeInformation['method'] ?? '__invoke');

        $title = '';
        // Loop through the Title attributes of the method and process them
        foreach ($this->getTitleAttributes($method) as $attribute) {
            $parentAttribute = $attribute->newInstance();
            $title .= ' - ' . $this->processTitle($parentAttribute->getTitle(), $parameters);

            if ($parentAttribute->hasParent()) {
                $title .= $this->getTitleFromParent($parentAttribute->getParent(), $parameters);
            }
        }

        return $title;
    }

    /**
     * Process the title string.
     *
     * @param array<mixed> $parameters
     */
    private function processTitle(string $title, array $parameters = []): string
    {
        // Replace the placeholders in the title with the actual parameters
        if (strpos($title, '{') !== false) {
            preg_match_all('/\{(.*?)\}/', $title, $matches);

            foreach ($matches[1] as $match) {
                $parts = explode('.', $match);

                if (!array_key_exists($parts[0], $parameters)) {
                    throw new \Exception(sprintf('Parameter %s not found in request', $parts[0]));
                }

                $replaceWith = count($parts) === 2
                    ? $this->propertyAccess->getValue($parameters[$parts[0]], $parts[1])
                    : $parameters[$parts[0]];

                $title = str_replace('{' . $match . '}', $replaceWith, $title);
            }
        }

        // Translate the title
        return $this->translator->trans($title);
    }

    /**
     * Get the information of a route.
     *
     * @return array<mixed>|null
     */
    private function getRouteInformation(string $name): ?array
    {
        // Get all the routes
        $routes = $this->router->getRouteCollection()->all();

        // Loop through the routes and find the one with the given name
        foreach ($routes as $key => $route) {
            if ($route->getDefault('_canonical_route') !== $name && $key !== $name) {
                continue;
            }

            // Get the controller and method of the route
            $controller = $route->getDefault('_controller');
            $hasMethod = strpos($controller, '::') > 0;
            $controllerClass = $hasMethod ? explode('::', $controller)[0] : $controller;
            $method = $hasMethod ? explode('::', $controller)[1] : '__invoke';

            // Get the required parameters of the route
            $requiredParameters = array_filter($route->compile()->getVariables(), fn($parameter) => $route->getDefault($parameter) === null);

            return [
                'controller' => $controllerClass,
                'method' => $method,
                'parameters' => $requiredParameters,
            ];
        }

        return null;
    }
}
