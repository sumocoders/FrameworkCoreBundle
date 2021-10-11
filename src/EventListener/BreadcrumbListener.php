<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use SumoCoders\FrameworkCoreBundle\Attribute\Breadcrumb as BreadcrumbAttribute;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;
use RuntimeException;

/*
 * This listener will fire on every registered controller
 * in our application, loop over all the methods in said
 * controller and attempt to process and generate a breadcrumb
 * for each valid attribute it finds on those methods.
 */
class BreadcrumbListener
{
    private RouterInterface $router;
    private BreadcrumbTrail $breadcrumbTrail;
    private Request $request;

    public function __construct(
        RouterInterface $router,
        BreadcrumbTrail $breadcrumbTrail
    ) {
        $this->router = $router;
        $this->breadcrumbTrail = $breadcrumbTrail;
    }

    public function onKernelController(KernelEvent $event): void
    {
        $controller = $event->getController();
        $this->request = $event->getRequest();

        if (!is_callable($controller)) {
            $controller = $controller[0];
        }

        if ($event->isMainRequest()) {
            $this->breadcrumbTrail->reset();
        }

        $this->processBreadcrumbs($controller);
    }

    private function processBreadcrumbs(callable $controller): void
    {
        // Build a new ReflectionClass instance of our controller
        $class = new \ReflectionClass($controller);

        if ($class->isAbstract()) {
            throw new InvalidArgumentException(sprintf('Attributes from class "%s" cannot be read as it is abstract.', $class));
        }

        $methods = $class->getMethods();

        foreach ($methods as $method) {
            $this->processAttributeFromMethod($method);
        }
    }

    private function processAttributeFromMethod(
        \Reflectionmethod $method,
        ?Route $route = null
    ) {
        $attributes = $method->getAttributes(BreadcrumbAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);

        foreach ($attributes as $attribute) {
            /** @var BreadcrumbAttribute $attributeInstance */
            $attributeInstance = $attribute->newInstance();
            dump($attributeInstance);
            if ($route !== null) {
                $attributeInstance->setRoute($route);
            }

            if ($attributeInstance->hasRoute()) {
                $this->verifyRoute($attributeInstance->getRoute());
            }

            if ($attributeInstance->hasParent()) {
                $this->addBreadcrumbsForParent($attributeInstance->getParent());
            }

            $this->breadcrumbTrail->add(
                $this->generateBreadcrumb(
                    $attributeInstance
                )
            );
        }
    }

    private function verifyRoute(Route $attributeRoute)
    {
        // Get the route
        $routeInformation = $this->getRouteInformation($attributeRoute->getName());

        if (count($routeInformation['parameters']) > 0 &&
            !$attributeRoute->getParameters()) {
            throw new RuntimeException(
                'Your breadcrumb route is missing required parameters: ' .
                implode($routeInformation['parameters'])
            );
        }

        foreach ($routeInformation['parameters'] as $requiredParameter) {
            if (!\array_key_exists($requiredParameter, $attributeRoute->getParameters())) {
                throw new RuntimeException('Your breadcrumb route is missing required parameters: ' . $requiredParameter);
            }
        }
    }

    private function generateBreadcrumb(BreadcrumbAttribute $breadcrumb): Breadcrumb
    {
        $title = $breadcrumb->getTitle();

        // Check if the passed value was an expression, e.g. {item.name}
        preg_match(
            '/{\K[^}]*(?=})/m',
            $title,
            $match
        );

        // The passed value was an expression
        if (\array_key_exists(0, $match)) {
            $expression = $match[0];

            // If no functions were used
            if (!str_contains($expression, '.')) {
                throw new RuntimeException('When using objects in a breadcrumb, you have to specify which method to read. E.g. {object.name}');
            }

            $methods = explode('.', $expression);
            $variableName = array_shift($methods);

            if (!$this->request->attributes->has($variableName)) {
                throw new RuntimeException('You tried to use {' . $variableName . '} as a breadcrumb parameter, but there is no parameter with that name in the route.');
            }

            $object = $this->request->attributes->get($variableName);

            //TODO: if $object is a string and not an object, the paramconversion failed and could'nt find a valid object. handle exception
            if (is_string($object)) {
                throw new RuntimeException('The parameter conversion ');
            }

            $title = $this->processMethodChain($object, $methods);
        }

        if ($breadcrumb->hasRoute()) {
            $routeName = $breadcrumb->getRoute()->getName();
            $routeParameters = $breadcrumb->getRoute()->getParameters();

            return new Breadcrumb(
                $title,
                $this->router->generate($routeName, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL)
            );
        }

        return new Breadcrumb(
            $title
        );
    }

    private function getRouteInformation(string $name): ?array
    {
        // Get all the routes defined in the entire application
        $routes = $this->router->getRouteCollection()->all();

        foreach ($routes as $route) {
            // Get our canonical (without a locale prefixed) route name
            if ($route->getDefault('_canonical_route') !== $name) {
                continue;
            }

            /*
             * In the case of multiple methods defined per controller,
             * explode the controller name and method
             */
            if (strpos('::', $route->getDefault('controller')) > 0) {
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

    private function addBreadcrumbsForParent(Route $parent): void
    {
        $routeName = $parent->getName();
        $routeInformation = $this->getRouteInformation($routeName);

        if ($routeInformation === null) {
            throw new RuntimeException(
                'A route with name "'. $routeName . '" could not be found. Check your spelling.'
            );
        }
        //TODO: check if we need to manually pass the attrbutes
        //since each attribute will resolve their own parameters
        //from the URL anyway
        $requiredParameters = $routeInformation['parameters'];

        $parentParameters = [];
        $currentAttributes = $this->request->attributes->all();

        foreach ($requiredParameters as $requiredParameterForParent) {
            /*
             * In real world scenario's, the parent is often present
             * in the same URI as the request. Take for example:
             *  /{author}/{book}
             * If we're currently in the book route, we can check the URI
             * for the author parameter and already fill it in.
             */
            if (\array_key_exists($requiredParameterForParent, $currentAttributes)) {
                $parentParameters[$requiredParameterForParent] = $currentAttributes[$requiredParameterForParent]->getId();
            }
        }

        $class = new \ReflectionClass($routeInformation['controller']);
        $method = $class->getMethod($routeInformation['method']);

        $this->processAttributeFromMethod($method, new Route($routeName, $parentParameters));
    }

    private function processMethodChain(
        object $object,
        array $methods
    ): string {
        foreach ($methods as $method) {
            $fullMethodNames = [
                'get' . $method,
                'has' . $method,
                'is' . $method,
            ];

            foreach ($fullMethodNames as $fullMethodName) {
                if (is_callable([$object, $fullMethodName])) {
                    $object = call_user_func([$object, $fullMethodName]);

                    continue 2;
                }

                throw new RuntimeException(sprintf('"%s" is not callable.',
                    implode('.', $methods)));
            }
        }

        return $object;
    }
}
