<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use SumoCoders\FrameworkCoreBundle\Exception\Breadcrumb\EntityNotFoundException;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        TranslatorInterface $translator
    ) {
        $this->router = $router;
        $this->propertyAccess = $propertyAccess;
        $this->breadcrumbTrail = $breadcrumbTrail;
        $this->manager = $manager;
        $this->translator = $translator;
    }

    public function onKernelController(KernelEvent $event): void
    {
        $controller = $event->getController();
        $this->request = $event->getRequest();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if ($event->isMainRequest()) {
            $this->breadcrumbTrail->reset();
        }

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
                    $class
                )
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
        ?Route $route = null
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
                $this->addBreadcrumbsForParent($attributeInstance->getParent());
            }

            try {
                $this->breadcrumbTrail->add(
                    $this->generateBreadcrumb(
                        $attributeInstance,
                        $method
                    )
                );
            } catch (EntityNotFoundException $e) {

            }
        }
    }

    private function generateBreadcrumb(
        BreadcrumbAttribute $breadcrumb,
        \Reflectionmethod $method
    ): Breadcrumb {
        $title = $breadcrumb->getTitle();
        $parameters = $breadcrumb->getParameters();

        // We're dealing with an expression, e.g. {item.name}
        if ($title[0] === '{' && $title[-1] === '}') {
            $expression = substr($title, 1, strlen($title) - 2);

            if (str_contains($expression, '.')) {
                $split = explode('.', $expression, 2);
                $attributeName = $split[0];
                $propertyPath = $split[1];
            } else {
                $attributeName = $expression;
            }

            if (!$this->request->attributes->has($attributeName)) {
                throw new RuntimeException(
                    'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                    'parameter with that name in the route.'
                );
            }

            $attributeId = $this->request->attributes->get($attributeName);

            $name = null;
            $mapping = null;
            foreach ($method->getParameters() as $parameter) {
                if ($parameter->name === $attributeName) {
                    $name = $parameter->getType()->getName();
                    foreach ($parameter->getAttributes() as $attribute) {
                        if ($attribute->getName() === MapEntity::class) {
                            $mapping = $attribute->newInstance()->mapping;
                        }
                    }
                }
            }

            if ($name === null) {
                throw new RuntimeException(
                    'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                    'parameter with that name in the route.'
                );
            }

            if ($mapping !== null && isset($mapping[$attributeName])) {
                $attribute = $this->manager->getRepository($name)->findOneBy([$mapping[$attributeName] => $attributeId]);
            } else {
                $attribute = $this->manager->getRepository($name)->find($attributeId);
            }

            if (!is_object($attribute)) {
                throw new EntityNotFoundException(
                    'Could not resolve entity ' . $name . ' with ID ' . $attributeId
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
            $this->resolveRouteParameters($breadcrumb);

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

                if (!$this->request->attributes->has($attributeName)) {
                    throw new RuntimeException(
                        'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                        'parameter with that name in the route.'
                    );
                }

                $attributeId = $this->request->attributes->get($attributeName);

                $name = null;
                foreach ($method->getParameters() as $parameter) {
                    if ($parameter->name === $attributeName) {
                        $name = $parameter->getType()->getName();
                    }
                }

                if ($name === null) {
                    throw new RuntimeException(
                        'You tried to use {' . $attributeName . '} as a breadcrumb parameter, but there is no ' .
                        'parameter with that name in the route.'
                    );
                }

                $attribute = $this->manager->getRepository($name)->find($attributeId);

                if (!is_object($attribute)) {
                    throw new RuntimeException(
                        'Could not resolve entity ' . $name . ' with ID ' . $attributeId
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

        // Just a simple string
        return new Breadcrumb($title);
    }

    private function addBreadcrumbsForParent(Route $parent): void
    {
        $routeName = $parent->getName();
        $routeInformation = $this->getRouteInformation($routeName);

        if ($routeInformation === null) {
            throw new RuntimeException(
                'A route with name "' . $routeName . '" could not be found. Check your spelling.'
            );
        }

        // If class contains :: in the name, we're dealing with a static method
        if (strpos($routeInformation['controller'], '::') > 0) {
            $parts = explode('::', $routeInformation['controller']);
            $class = new \ReflectionClass($parts[0]);

            $method = $class->getMethod($parts[1]);
        } else {
            $class = new \ReflectionClass($routeInformation['controller']);
            $method = $class->getMethod($routeInformation['method']);
        }

        $this->processAttributeFromMethod($method, $class, new Route($routeName));
    }

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
            if (strpos($route->getDefault('_controller'), '::') > 0) {
                $chunk = explode('::', $route->getDefault('_controller'));
                $controller = $chunk[0];
                $method = $chunk[1];
            } else {
                $controller = $route->getDefault('_controller');
            }

            // Compile the route to access the parameters
            $compiledRoute = $route->compile();
            $parameters = $compiledRoute->getVariables();

            // Loop each parameter and check if a default exists for it
            $requiredParameters = [];
            foreach ($parameters as $parameter) {
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
        $routeInformation = $this->getRouteInformation($route->getName());
        $requiredParameters = $routeInformation['parameters'];

        $parentParameters = [];
        $currentAttributes = $this->request->attributes->all();

        foreach ($requiredParameters as $requiredParentParameter) {
            /*
             * In real world scenario's, the parent is often present
             * in the same URI as the request. Take for example:
             *  /{item}/{child}
             * If we're currently in the child route, we can check the URI
             * for the author parameter and already fill it in.
             */
            if (\array_key_exists($requiredParentParameter, $currentAttributes)) {
                if (is_object($currentAttributes[$requiredParentParameter])) {
                    $parentParameters[$requiredParentParameter] = $currentAttributes[$requiredParentParameter]->getId();
                } else {
                    $parentParameters[$requiredParentParameter] = $currentAttributes[$requiredParentParameter];
                }
            }
        }

        $route->addParameters($parentParameters);

        if (
            count($routeInformation['parameters']) > 0
            && !$route->getParameters()
        ) {
            throw new RuntimeException(
                'Your breadcrumb route is missing required parameters: ' .
                implode($routeInformation['parameters'])
            );
        }

        foreach ($routeInformation['parameters'] as $requiredParameter) {
            if (!\array_key_exists($requiredParameter, $route->getParameters())) {
                throw new RuntimeException(
                    'Your breadcrumb route is missing required parameters: ' . $requiredParameter
                );
            }
        }
    }
}
