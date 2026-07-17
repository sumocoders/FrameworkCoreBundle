<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use SumoCoders\FrameworkCoreBundle\Exception\Breadcrumb\EntityNotFoundException;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

// @mago-expect lint:halstead,kan-defect,cyclomatic-complexity
class BreadcrumbListener
{
    private RouterInterface $router;
    private PropertyAccessorInterface $propertyAccess;
    private BreadcrumbTrail $breadcrumbTrail;
    private Request $request;
    private EntityManagerInterface $manager;
    private TranslatorInterface $translator;

    public function __construct(
        RouterInterface $router,
        PropertyAccessorInterface $propertyAccess,
        BreadcrumbTrail $breadcrumbTrail,
        EntityManagerInterface $manager,
        TranslatorInterface $translator,
    ) {
        $this->router = $router;
        $this->propertyAccess = $propertyAccess;
        $this->breadcrumbTrail = $breadcrumbTrail;
        $this->manager = $manager;
        $this->translator = $translator;
    }

    public function onKernelController(KernelEvent $event): void
    {
        // @mago-expect analysis:mixed-assignment,non-existent-method
        $controller = $event->getController();
        $this->request = $event->getRequest();

        if (is_array($controller)) {
            // @mago-expect analysis:mixed-assignment
            $controller = $controller[0];
        }

        if ($event->isMainRequest()) {
            $this->breadcrumbTrail->reset();
        }

        // @mago-expect analysis:mixed-argument
        $this->processBreadcrumbs($controller);
    }

    private function processBreadcrumbs(object $controller): void
    {
        // Build a new ReflectionClass instance of our controller
        $class = new \ReflectionClass($controller);

        if ($class->isAbstract()) {
            throw new InvalidArgumentException(
                sprintf(
                    'Attributes from class "%s" cannot be read as it is abstract.',
                    $class,
                ),
            );
        }

        $methods = $class->getMethods(\ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            $this->processAttributeFromMethod($method, $class);
        }
    }

    private function processAttributeFromMethod(
        \Reflectionmethod $method,
        \ReflectionClass $class,
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
                $this->addBreadcrumbsForParent($attributeInstance->getParent());
            }

            try {
                $this->breadcrumbTrail->add(
                    $this->generateBreadcrumb(
                        $attributeInstance,
                        $method,
                    ),
                );

                // @mago-expect lint:no-empty-catch-clause
            } catch (EntityNotFoundException $e) {
            }
        }
    }

    private function generateBreadcrumb(
        BreadcrumbAttribute $breadcrumb,
        \Reflectionmethod $method,
    ): Breadcrumb {
        $title = $breadcrumb->getTitle();
        $parameters = $breadcrumb->getParameters();

        // We're dealing with an expression, e.g. {item.name}
        if ($title[0] === '{' && $title[-1] === '}') {
            $expression = substr($title, 1, strlen($title) - 2);

            // @mago-expect lint:no-else-clause
            if (str_contains($expression, '.')) {
                $split = explode('.', $expression, 2);
                $attributeName = $split[0];
                $propertyPath = $split[1];
            } else {
                $attributeName = $expression;
            }

            if (!$this->request->attributes->has($attributeName)) {
                throw new RuntimeException(
                    'You tried to use {'
                    . $attributeName
                    . '} as a breadcrumb parameter, but there is no '
                    . 'parameter with that name in the route.',
                );
            }

            // @mago-expect analysis:mixed-assignment
            $attributeId = $this->request->attributes->get($attributeName);

            $name = null;
            $mapping = null;
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->name === $attributeName) {
                    // @mago-expect analysis:possible-method-access-on-null,non-existent-method(2),mixed-assignment
                    $name = $parameter->getType()->getName();
                    // @mago-expect lint:prefer-early-continue
                    foreach ($parameter->getAttributes() as $attribute) {
                        // @mago-expect lint:prefer-early-continue
                        if ($attribute->getName() === MapEntity::class) {
                            // @mago-expect analysis:ambiguous-object-property-access,mixed-assignment
                            // @phpstan-ignore property.notFound
                            $mapping = $attribute->newInstance()->mapping;
                        }
                    }
                }
            }

            if ($name === null) {
                throw new RuntimeException(
                    'You tried to use {'
                    . $attributeName
                    . '} as a breadcrumb parameter, but there is no '
                    . 'parameter with that name in the route.',
                );
            }

            // @mago-expect lint:no-isset,no-else-clause
            if ($mapping !== null && isset($mapping[$attributeName])) {
                // @mago-expect analysis:mixed-argument,invalid-array-element-key,less-specific-argument
                $attribute = $this->manager
                    ->getRepository($name)
                    ->findOneBy([$mapping[$attributeName] => $attributeId]);
            } else {
                // @mago-expect analysis:mixed-argument
                $attribute = $this->manager->getRepository($name)->find($attributeId);
            }

            if (!is_object($attribute)) {
                // @mago-expect analysis:mixed-operand(2)
                throw new EntityNotFoundException(
                    'Could not resolve entity ' . $name . ' with ID ' . $attributeId,
                );
            }

            // @mago-expect lint:no-isset
            if (!isset($propertyPath)) {
                throw new RuntimeException(
                    'When using objects in a breadcrumb, you have to specify which method to read.'
                    . ' E.g. {object.name}',
                );
            }

            // @mago-expect analysis:mixed-argument,mixed-assignment
            $title = $this->propertyAccess->getValue($attribute, $propertyPath);
        }

        if ($breadcrumb->hasRoute()) {
            $this->resolveRouteParameters($breadcrumb);

            // @mago-expect analysis:mixed-argument(3),possible-method-access-on-null(2)
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
                if (!$this->request->attributes->has($attributeName)) {
                    // @mago-expect analysis:mixed-operand
                    throw new RuntimeException(
                        'You tried to use {'
                        . $attributeName
                        . '} as a breadcrumb parameter, but there is no '
                        . 'parameter with that name in the route.',
                    );
                }

                // @mago-expect analysis:mixed-argument,mixed-assignment
                $attributeId = $this->request->attributes->get($attributeName);

                $name = null;
                foreach ($method->getParameters() as $parameter) {
                    // @mago-expect lint:prefer-early-continue
                    if ($parameter->name === $attributeName) {
                        // @mago-expect analysis:possible-method-access-on-null,non-existent-method(2),mixed-assignment
                        $name = $parameter->getType()->getName();
                    }
                }

                if ($name === null) {
                    // @mago-expect analysis:mixed-operand
                    throw new RuntimeException(
                        'You tried to use {'
                        . $attributeName
                        . '} as a breadcrumb parameter, but there is no '
                        . 'parameter with that name in the route.',
                    );
                }

                // @mago-expect analysis:mixed-argument
                $attribute = $this->manager->getRepository($name)->find($attributeId);

                if (!is_object($attribute)) {
                    // @mago-expect analysis:mixed-operand(2)
                    throw new RuntimeException(
                        'Could not resolve entity ' . $name . ' with ID ' . $attributeId,
                    );
                }

                // @mago-expect lint:no-isset
                if (!isset($propertyPath)) {
                    throw new RuntimeException(
                        'When using objects in a breadcrumb, you have to specify which method to read.'
                        . ' E.g. {object.name}',
                    );
                }

                // @mago-expect analysis:mixed-argument
                $parameters[$key] = $this->propertyAccess->getValue($attribute, $propertyPath);
            }

            // @mago-expect analysis:mixed-argument
            $title = $this->translator->trans($title, $parameters);
        }

        // Just a simple string
        // @mago-expect analysis:mixed-argument
        return new Breadcrumb($title);
    }

    private function addBreadcrumbsForParent(Route $parent): void
    {
        $routeName = $parent->getName();
        $routeInformation = $this->getRouteInformation($routeName);

        if ($routeInformation === null) {
            throw new RuntimeException(
                'A route with name "' . $routeName . '" could not be found. Check your spelling.',
            );
        }

        // If class contains :: in the name, we're dealing with a static method
        // @mago-expect lint:no-else-clause
        // @mago-expect analysis:mixed-argument,possibly-false-operand
        if (strpos($routeInformation['controller'], '::') > 0) {
            // @mago-expect analysis:mixed-argument
            $parts = explode('::', $routeInformation['controller']);
            // @mago-expect analysis:possibly-invalid-argument
            // @phpstan-ignore argument.type
            $class = new \ReflectionClass($parts[0]);

            $method = $class->getMethod($parts[1]);
        } else {
            // @mago-expect analysis:mixed-argument
            $class = new \ReflectionClass($routeInformation['controller']);
            // @mago-expect analysis:mixed-argument
            $method = $class->getMethod($routeInformation['method']);
        }

        $this->processAttributeFromMethod($method, $class, new Route($routeName));
    }

    // @mago-expect analysis:imprecise-type
    // @phpstan-ignore missingType.iterableValue
    private function getRouteInformation(string $name): ?array
    {
        // Get all the routes defined in the entire application
        $routes = $this->router->getRouteCollection()->all();

        foreach ($routes as $key => $route) {
            // Get our canonical (without a locale prefixed) route name
            if ($route->getDefault('_canonical_route') !== $name && $key !== $name) {
                continue;
            }

            /*
             * In the case of multiple methods defined per controller,
             * explode the controller name and method
             */
            // @mago-expect lint:no-else-clause
            // @mago-expect analysis:mixed-argument,possibly-false-operand
            if (strpos($route->getDefault('_controller'), '::') > 0) {
                // @mago-expect analysis:mixed-argument
                $chunk = explode('::', $route->getDefault('_controller'));
                $controller = $chunk[0];
                $method = $chunk[1];
            } else {
                // @mago-expect analysis:mixed-assignment
                $controller = $route->getDefault('_controller');
            }

            // Compile the route to access the parameters
            $compiledRoute = $route->compile();
            $parameters = $compiledRoute->getVariables();

            // Loop each parameter and check if a default exists for it
            $requiredParameters = [];
            // @mago-expect analysis:mixed-assignment
            foreach ($parameters as $parameter) {
                // @mago-expect lint:prefer-early-continue
                // @mago-expect analysis:mixed-argument
                if ($route->getDefault($parameter) === null) {
                    $requiredParameters[] = $parameter;
                }
            }

            // Return the controller, method and required parameters
            return [
                'controller' => $controller,
                'method' => $method ?? '__invoke',
                'parameters' => $requiredParameters,
            ];
        }

        return null;
    }

    private function resolveRouteParameters(BreadcrumbAttribute $breadcrumb): void
    {
        $route = $breadcrumb->getRoute();
        // @mago-expect analysis:mixed-argument,possible-method-access-on-null
        $routeInformation = $this->getRouteInformation($route->getName());
        // @mago-expect analysis:mixed-assignment,possibly-null-array-access
        $requiredParameters = $routeInformation['parameters'];

        $parentParameters = [];
        $currentAttributes = $this->request->attributes->all();

        // @mago-expect analysis:invalid-iterator,mixed-assignment
        foreach ($requiredParameters as $requiredParentParameter) {
            /*
             * In real world scenario's, the parent is often present
             * in the same URI as the request. Take for example:
             *  /{item}/{child}
             * If we're currently in the child route, we can check the URI
             * for the author parameter and already fill it in.
             */
            // @mago-expect lint:prefer-early-continue
            // @mago-expect analysis:mixed-argument
            if (\array_key_exists($requiredParentParameter, $currentAttributes)) {
                // @mago-expect lint:no-else-clause
                if (is_object($currentAttributes[$requiredParentParameter])) {
                    // @mago-expect analysis:ambiguous-object-method-access
                    $parentParameters[$requiredParentParameter] = $currentAttributes[$requiredParentParameter]->getId();
                } else {
                    $parentParameters[$requiredParentParameter] = $currentAttributes[$requiredParentParameter];
                }
            }
        }

        // @mago-expect analysis:possible-method-access-on-null
        $route->addParameters($parentParameters);

        // @mago-expect analysis:mixed-argument,possible-method-access-on-null,possibly-null-array-access
        if (count($routeInformation['parameters']) > 0 && !$route->getParameters()) {
            // @mago-expect analysis:mixed-argument
            throw new RuntimeException(
                'Your breadcrumb route is missing required parameters: ' . implode($routeInformation['parameters']),
            );
        }

        // @mago-expect analysis:invalid-iterator,mixed-assignment,possibly-null-array-access
        foreach ($routeInformation['parameters'] as $requiredParameter) {
            // @mago-expect analysis:mixed-argument(2),possible-method-access-on-null
            if (!\array_key_exists($requiredParameter, $route->getParameters())) {
                throw new RuntimeException(
                    // @mago-expect analysis:mixed-operand
                    'Your breadcrumb route is missing required parameters: ' . $requiredParameter,
                );
            }
        }
    }
}
