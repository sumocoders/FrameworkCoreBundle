<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use InvalidArgumentException;
use RuntimeException;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use SumoCoders\FrameworkCoreBundle\Exception\Breadcrumb\EntityNotFoundException;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// @mago-expect lint:halstead,kan-defect,cyclomatic-complexity
readonly class BreadcrumbListener
{
    public function __construct(
        private RouterInterface $router,
        private PropertyAccessorInterface $propertyAccess,
        private BreadcrumbTrail $breadcrumbTrail,
        private TranslatorInterface $translator,
    ) {
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->breadcrumbTrail->reset();
        }

        // @mago-expect analysis:mixed-assignment
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
                sprintf('Attributes from class "%s" cannot be read as it is abstract.', $class->getName()),
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
            $attributes = array_merge($attributes, $class->getAttributes(
                BreadcrumbAttribute::class,
                \ReflectionAttribute::IS_INSTANCEOF,
            ));
        }

        foreach ($attributes as $attribute) {
            $attributeInstance = $attribute->newInstance();

            if ($route !== null) {
                $attributeInstance->setRoute($route);
            }

            if ($attributeInstance->hasParent()) {
                // @mago-expect analysis:possibly-null-argument
                $this->addBreadcrumbsForParent($attributeInstance->getParent(), $namedArguments, $request);
            }

            try {
                $this->breadcrumbTrail->add(
                    $this->generateBreadcrumb($attributeInstance, $namedArguments, $request),
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
            $expression = substr($title, 1, -1);
            $propertyPath = null;

            // @mago-expect lint:no-else-clause
            if (str_contains($expression, '.')) {
                $split = explode('.', $expression, 2);
                $attributeName = $split[0];
                $propertyPath = $split[1];
            } else {
                $attributeName = $expression;
            }

            if (!array_key_exists($attributeName, $namedArguments)) {
                throw new RuntimeException(
                    'You tried to use {'
                    . $attributeName
                    . '} as a breadcrumb parameter, but there is no '
                    . 'parameter with that name in the route.',
                );
            }

            $attribute = $namedArguments[$attributeName];

            if (!is_object($attribute)) {
                // @mago-expect analysis:mixed-operand
                throw new EntityNotFoundException(
                    'Could not resolve entity for parameter ' . $attributeName,
                );
            }

            if ($propertyPath === null) {
                throw new RuntimeException(
                    'When using objects in a breadcrumb, you have to specify which method to read.'
                    . ' E.g. {object.name}',
                );
            }

            // @mago-expect analysis:mixed-assignment
            $title = $this->propertyAccess->getValue($attribute, $propertyPath);
        }

        if ($breadcrumb->hasRoute()) {
            $this->resolveRouteParameters($breadcrumb, $namedArguments, $request);

            // @mago-expect analysis:mixed-argument,possible-method-access-on-null(2),possibly-null-argument
            return new Breadcrumb(
                $title,
                $this->router->generate(
                    $breadcrumb->getRoute()->getName(),
                    $breadcrumb->getRoute()->getParameters(),
                    UrlGeneratorInterface::ABSOLUTE_URL,
                ),
            );
        }

        if (count($parameters)) {
            // @mago-expect analysis:mixed-assignment
            foreach ($parameters as $key => $parameterValue) {
                $propertyPath = null;
                // @mago-expect lint:no-else-clause
                // @mago-expect analysis:mixed-argument
                if (str_contains($parameterValue, '.')) {
                    // @mago-expect analysis:mixed-argument
                    $split = explode('.', $parameterValue, 2);
                    $attributeName = $split[0];
                    $propertyPath = $split[1];
                } else {
                    // @mago-expect analysis:mixed-assignment
                    $attributeName = $parameterValue;
                }

                // @mago-expect analysis:mixed-argument
                if (!array_key_exists($attributeName, $namedArguments)) {
                    throw new RuntimeException(
                        'You tried to use {'
                        . $attributeName
                        . '} as a breadcrumb parameter, but there is no '
                        . 'parameter with that name in the route.',
                    );
                }

                // @mago-expect analysis:mixed-assignment
                $attribute = $namedArguments[$attributeName];

                if (!is_object($attribute)) {
                    throw new RuntimeException(
                        'Could not resolve entity for parameter ' . $attributeName,
                    );
                }

                if ($propertyPath === null) {
                    throw new RuntimeException(
                        'When using objects in a breadcrumb, you have to specify which method to read.'
                        . ' E.g. {object.name}',
                    );
                }

                $parameters[$key] = $this->propertyAccess->getValue($attribute, $propertyPath);
            }

            // @mago-expect analysis:mixed-argument
            $title = $this->translator->trans($title, $parameters);
        }

        // @mago-expect analysis:mixed-argument
        return new Breadcrumb($title);
    }

    /** @param array<string, mixed> $namedArguments */
    private function addBreadcrumbsForParent(Route $parent, array $namedArguments, Request $request): void
    {
        $routeName = $parent->getName();
        $routeInformation = $this->getRouteInformation($routeName);

        if ($routeInformation === null) {
            throw new RuntimeException(
                'A route with name "' . $routeName . '" could not be found. Check your spelling.',
            );
        }

        // @mago-expect analysis:possibly-invalid-argument
        $class = new \ReflectionClass($routeInformation['controller']);
        $method = $class->getMethod($routeInformation['method']);

        $this->processAttributeFromMethod($method, $class, $namedArguments, $request, new Route($routeName));
    }

    /** @return array{controller: string, method: string, parameters: array<array-key, string>}|null */
    private function getRouteInformation(string $name): ?array
    {
        $routes = $this->router->getRouteCollection()->all();

        foreach ($routes as $key => $route) {
            if ($route->getDefault('_canonical_route') !== $name && $key !== $name) {
                continue;
            }

            /** @var string $controller */
            $controller = $route->getDefault('_controller');
            $hasMethod = str_contains($controller, '::');
            $controllerClass = $hasMethod ? explode('::', $controller)[0] : $controller;
            $method = $hasMethod ? explode('::', $controller)[1] : '__invoke';

            $compiledRoute = $route->compile();
            /** @var array<array-key, string> $variables */
            $variables = $compiledRoute->getVariables();
            $requiredParameters = array_filter(
                $variables,
                static fn (string $parameter): bool => $route->getDefault($parameter) === null,
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
    private function resolveRouteParameters(
        BreadcrumbAttribute $breadcrumb,
        array $namedArguments,
        Request $request,
    ): void {
        $route = $breadcrumb->getRoute();
        // @mago-expect analysis:possible-method-access-on-null
        $routeInformation = $this->getRouteInformation($route->getName());
        // @mago-expect analysis:possibly-null-array-access
        $requiredParameters = $routeInformation['parameters'];

        $parentParameters = [];

        // @mago-expect analysis:possibly-null-iterator
        foreach ($requiredParameters as $requiredParentParameter) {
            if (array_key_exists($requiredParentParameter, $namedArguments)) {
                // @mago-expect analysis:mixed-assignment
                $value = $namedArguments[$requiredParentParameter];
                // @mago-expect analysis:ambiguous-object-method-access
                $parentParameters[$requiredParentParameter] = is_object($value) ? $value->getId() : $value;
            } elseif ($request->attributes->has($requiredParentParameter)) {
                $parentParameters[$requiredParentParameter] = $request->attributes->get($requiredParentParameter);
            }
        }

        // @mago-expect analysis:possible-method-access-on-null
        $route->addParameters($parentParameters);

        // @mago-expect analysis:possibly-null-array-access,possibly-null-argument,possible-method-access-on-null
        if (count($routeInformation['parameters']) > 0 && !$route->getParameters()) {
            throw new RuntimeException(
                'Your breadcrumb route is missing required parameters: ' . implode($routeInformation['parameters']),
            );
        }

        // @mago-expect analysis:possibly-null-array-access,possibly-null-iterator
        foreach ($routeInformation['parameters'] as $requiredParameter) {
            // @mago-expect analysis:possible-method-access-on-null,possibly-null-argument
            if (!array_key_exists($requiredParameter, $route->getParameters())) {
                throw new RuntimeException(
                    'Your breadcrumb route is missing required parameters: ' . $requiredParameter,
                );
            }
        }
    }
}
