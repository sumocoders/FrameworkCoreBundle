<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\Exception\Breadcrumb\EntityNotFoundException;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\Routing\RouterInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

class BreadcrumbListener
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly PropertyAccessorInterface $propertyAccess,
        private readonly BreadcrumbTrail $breadcrumbTrail,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->breadcrumbTrail->reset();
        }

        $controller = $event->getController();

        if (is_array($controller)) {
            [$object, $methodName] = $controller;
            $class = new \ReflectionClass($object);
            $method = $class->getMethod($methodName);
        } elseif (is_object($controller) && !$controller instanceof \Closure) {
            $class = new \ReflectionClass($controller);
            $method = $class->getMethod('__invoke');
        } else {
            return;
        }

        if ($class->isAbstract()) {
            throw new InvalidArgumentException(
                sprintf('Attributes from class "%s" cannot be read as it is abstract.', $class->getName())
            );
        }

        $this->processAttributeFromMethod($method, $class, $event->getNamedArguments(), $event->getRequest());
    }

    /** @param array<string, mixed> $namedArguments */
    private function processAttributeFromMethod(
        \ReflectionMethod $method,
        \ReflectionClass $class,
        array $namedArguments,
        Request $request,
        ?Route $route = null,
    ): void {
        $attributes = $method->getAttributes(BreadcrumbAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
        if ($method->name === '__invoke' || $attributes !== []) {
            $attributes = array_merge($attributes, $class->getAttributes(BreadcrumbAttribute::class, \ReflectionAttribute::IS_INSTANCEOF));
        }

        foreach ($attributes as $attribute) {
            /** @var BreadcrumbAttribute $attributeInstance */
            $attributeInstance = $attribute->newInstance();

            if ($route !== null) {
                $attributeInstance->setRoute($route);
            }

            if ($attributeInstance->hasParent()) {
                $this->addBreadcrumbsForParent($attributeInstance->getParent(), $namedArguments, $request);
            }

            try {
                $this->breadcrumbTrail->add(
                    $this->generateBreadcrumb($attributeInstance, $namedArguments, $request)
                );
            } catch (EntityNotFoundException $e) {
            }
        }
    }

    /**
     * @param array<string, mixed> $namedArguments
     */
    private function generateBreadcrumb(
        BreadcrumbAttribute $breadcrumb,
        array $namedArguments,
        Request $request,
    ): Breadcrumb {
        $title = $breadcrumb->getTitle();
        $parameters = $breadcrumb->getParameters();

        if ($title[0] === '{' && $title[-1] === '}') {
            $expression = substr($title, 1, strlen($title) - 2);

            if (str_contains($expression, '.')) {
                $split = explode('.', $expression, 2);
                $attributeName = $split[0];
                $propertyPath = $split[1];
            } else {
                $attributeName = $expression;
            }

            if (!array_key_exists($attributeName, $namedArguments)) {
                throw new RuntimeException(
                    'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                    'parameter with that name in the route.'
                );
            }

            $attribute = $namedArguments[$attributeName];

            if (!is_object($attribute)) {
                throw new EntityNotFoundException(
                    'Could not resolve entity for parameter ' . $attributeName
                );
            }

            if (!isset($propertyPath)) {
                throw new RuntimeException(
                    'When using objects in a breadcrumb, you have to specify which method to read.' .
                    ' E.g. {object.name}'
                );
            }

            $title = $this->propertyAccess->getValue($attribute, $propertyPath);
        }

        if ($breadcrumb->hasRoute()) {
            $this->resolveRouteParameters($breadcrumb, $namedArguments, $request);

            return new Breadcrumb(
                $title,
                $this->router->generate(
                    $breadcrumb->getRoute()->getName(),
                    $breadcrumb->getRoute()->getParameters(),
                    UrlGeneratorInterface::ABSOLUTE_URL
                )
            );
        }

        if (count($parameters)) {
            foreach ($parameters as $key => $parameterValue) {
                if (str_contains($parameterValue, '.')) {
                    $split = explode('.', $parameterValue, 2);
                    $attributeName = $split[0];
                    $propertyPath = $split[1];
                } else {
                    $attributeName = $parameterValue;
                }

                if (!array_key_exists($attributeName, $namedArguments)) {
                    throw new RuntimeException(
                        'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                        'parameter with that name in the route.'
                    );
                }

                $attribute = $namedArguments[$attributeName];

                if (!is_object($attribute)) {
                    throw new RuntimeException(
                        'Could not resolve entity for parameter ' . $attributeName
                    );
                }

                if (!isset($propertyPath)) {
                    throw new RuntimeException(
                        'When using objects in a breadcrumb, you have to specify which method to read.' .
                        ' E.g. {object.name}'
                    );
                }

                $parameters[$key] = $this->propertyAccess->getValue($attribute, $propertyPath);
            }

            $title = $this->translator->trans($title, $parameters);
        }

        return new Breadcrumb($title);
    }

    /** @param array<string, mixed> $namedArguments */
    private function addBreadcrumbsForParent(Route $parent, array $namedArguments, Request $request): void
    {
        $routeName = $parent->getName();
        $routeInformation = $this->getRouteInformation($routeName);

        if ($routeInformation === null) {
            throw new RuntimeException(
                'A route with name "' . $routeName . '" could not be found. Check your spelling.'
            );
        }

        $class = new \ReflectionClass($routeInformation['controller']);
        $method = $class->getMethod($routeInformation['method']);

        $this->processAttributeFromMethod($method, $class, $namedArguments, $request, new Route($routeName));
    }

    /** @return array<mixed>|null */
    private function getRouteInformation(string $name): ?array
    {
        $routes = $this->router->getRouteCollection()->all();

        foreach ($routes as $key => $route) {
            if ($route->getDefault('_canonical_route') !== $name && $key !== $name) {
                continue;
            }

            $controller = $route->getDefault('_controller');
            $hasMethod = strpos($controller, '::') > 0;
            $controllerClass = $hasMethod ? explode('::', $controller)[0] : $controller;
            $method = $hasMethod ? explode('::', $controller)[1] : '__invoke';

            $compiledRoute = $route->compile();
            $requiredParameters = array_filter(
                $compiledRoute->getVariables(),
                fn($parameter) => $route->getDefault($parameter) === null
            );

            return [
                'controller' => $controllerClass,
                'method' => $method,
                'parameters' => $requiredParameters,
            ];
        }

        return null;
    }

    /** @param array<string, mixed> $namedArguments */
    private function resolveRouteParameters(BreadcrumbAttribute $breadcrumb, array $namedArguments, Request $request): void
    {
        $route = $breadcrumb->getRoute();
        $routeInformation = $this->getRouteInformation($route->getName());
        $requiredParameters = $routeInformation['parameters'];

        $parentParameters = [];

        foreach ($requiredParameters as $requiredParentParameter) {
            if (array_key_exists($requiredParentParameter, $namedArguments)) {
                $value = $namedArguments[$requiredParentParameter];
                $parentParameters[$requiredParentParameter] = is_object($value) ? $value->getId() : $value;
            } elseif ($request->attributes->has($requiredParentParameter)) {
                $parentParameters[$requiredParentParameter] = $request->attributes->get($requiredParentParameter);
            }
        }

        $route->addParameters($parentParameters);

        if (count($routeInformation['parameters']) > 0 && !$route->getParameters()) {
            throw new RuntimeException(
                'Your breadcrumb route is missing required parameters: ' .
                implode($routeInformation['parameters'])
            );
        }

        foreach ($routeInformation['parameters'] as $requiredParameter) {
            if (!array_key_exists($requiredParameter, $route->getParameters())) {
                throw new RuntimeException(
                    'Your breadcrumb route is missing required parameters: ' . $requiredParameter
                );
            }
        }
    }
}
