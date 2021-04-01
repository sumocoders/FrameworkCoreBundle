<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Doctrine\Common\Annotations\Reader;
use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use SumoCoders\FrameworkCoreBundle\Annotation\Breadcrumb as BreadcrumbAnnotation;
use SumoCoders\FrameworkCoreBundle\Service\BreadcrumbTrail;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\Routing\CompiledRoute;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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
        $controller = $event->getController();
        if (!is_array($controller)) {
            return;
        }

        if ($event->isMasterRequest()) {
            $this->breadcrumbTrail->reset();
        }

        $this->processAnnotations($event, $controller);
    }

    private function processAnnotations(KernelEvent $event, array $controller): void
    {
        $class = new ReflectionClass($controller[0]);
        if ($class->isAbstract()) {
            throw new InvalidArgumentException(sprintf('Annotations from class "%s" cannot be read as it is abstract.', $class));
        }

        $method = $class->getMethod($controller[1]);
        if ($event->isMasterRequest()) {
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
        $class = new ReflectionClass($controller[0]);
        $method = $class->getMethod($controller[1]);

        if ($event->isMasterRequest()) {
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
            if (!$annotation instanceof BreadcrumbAnnotation) {
                continue;
            }

            if ($annotation->getParentRouteName()) {
                $this->addBreadcrumbsForParent($annotation, $event);
            }

            $this->breadcrumbTrail->add(
                $this->generateBreadcrumb(
                    $event->getRequest(),
                    $annotation,
                    $routeName,
                    $parameters
                )
            );
        }
    }

    private function generateBreadcrumb(
        Request $request,
        BreadcrumbAnnotation $breadcrumb,
        ?string $route = null,
        ?array $parameters = null
    ): Breadcrumb {
        $title = $breadcrumb->getTitle();
        $routeParameters = $parameters ?? $breadcrumb->getRouteParameters();
        $routeName = $route ?? $breadcrumb->getRouteName();
        preg_match_all(
            '#\{(?P<variable>\w+).?(?P<function>([\w\.])*):?(?P<parameters>(\w|,| )*)\}#',
            $title,
            $matches,
            PREG_OFFSET_CAPTURE | PREG_SET_ORDER
        );

        // Convert the breadcrumb title to the correct value
        foreach ($matches as $match) {
            $varName = $match['variable'][0];
            // Get the methods that need to be called on the route parameter
            $functions = $match['function'][0] ? explode('.', $match['function'][0]) : [];
            $parameters = $match['parameters'][0] ? explode(',', $match['parameters'][0]) : [];
            $nbCalls = count($functions);

            if ($request->attributes->has($varName)) {
                $object = $request->attributes->get($varName);

                // If no functions need to be called use the title as given
                if (empty($functions)) {
                    $objectValue = (string) $object;
                    $title = str_replace($match[0][0], $objectValue, $title);

                    continue;
                }

                // Chain the method calls so we can get the correct value
                // Example book.author.name should call getAuthor().getName() on the given book object
                $objectValue = $this->processFunctionChain($functions, $nbCalls, $object, $varName, $parameters);

                $title = str_replace($match[0][0], $objectValue, $title);
            }
        }

        // Convert the route parameters to a usable format
        foreach ($routeParameters as $key => $value) {
            if (is_numeric($key)) {
                $routeParameters[$value] = $request->get($value);
                unset($routeParameters[$key]);

                continue;
            }

            if (preg_match('#^\{(?P<parameter>\w+)\}$#', $value, $matches)) {
                $routeParameters[$key] = $request->get($matches['parameter']);

                continue;
            }

            if (preg_match_all(
                '#\{(?P<variable>\w+).?(?P<function>([\w\.])*):?(?P<parameters>(\w|,| )*)\}#',
                $value,
                $matches,
                PREG_OFFSET_CAPTURE | PREG_SET_ORDER
            )) {
                foreach ($matches as $match) {
                    $varName = $match['variable'][0];
                    // Get the methods that need to be called on the route parameter
                    $functions = $match['function'][0] ? explode('.', $match['function'][0]) : [];
                    $parameters = $match['parameters'][0] ? explode(',', $match['parameters'][0]) : [];
                    $nbCalls = count($functions);

                    if (!$request->attributes->has($varName)) {
                      continue;
                    }

                    $object = $request->attributes->get($varName);
                    if (empty($functions)) {
                        $objectValue = (string) $object;
                        $routeParameter = str_replace($match[0][0], $objectValue, $value);
                        $routeParameters[$key] = $routeParameter;

                        continue;
                    }

                    // Chain the method calls so we can get the correct value
                    // Example book.author.name should call getAuthor().getName() on the given book object
                    $objectValue = $this->processFunctionChain($functions, $nbCalls, $object, $varName, $parameters);

                    $routeParameter = str_replace($match[0][0], $objectValue, $value);
                    $routeParameters[$key] = $routeParameter;
                }
            }
        }

        $url = null;
        if ($routeName !== null) {
            $url = $this->router->generate($routeName, $routeParameters, UrlGeneratorInterface::ABSOLUTE_URL);
        }

        return new Breadcrumb(
            $title,
            $url
        );
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

    private function addBreadcrumbsForParent(BreadcrumbAnnotation $annotation, KernelEvent $event): void
    {
        $controller = $this->getControllerFromName(
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

    private function processFunctionChain(
        array $functions,
        int $nbCalls,
        $object,
        string $varName,
        array $parameters
    ): string {
        foreach ($functions as $f => $function) {
            $fullFunctionNames = [
                'get'.$function,
                'has'.$function,
                'is'.$function,
            ];

            // While this is not the last function, call the chain
            if ($f < $nbCalls - 1) {
                foreach ($fullFunctionNames as $fullFunctionName) {
                    if (is_callable([$object, $fullFunctionName])) {
                        $object = call_user_func([$object, $fullFunctionName]);

                        continue 2;
                    }
                }

                throw new RuntimeException(sprintf('"%s" is not callable.',
                    implode('.', array_merge([$varName], $functions))));
            }

            // End of the chain: call the method
            foreach ($fullFunctionNames as $fullFunctionName) {
                if (is_callable([$object, $fullFunctionName])) {
                    $objectValue = call_user_func_array([$object, $fullFunctionName], $parameters);

                    continue 2;
                }
            }

            throw new RuntimeException(sprintf('"%s" is not callable.',
                implode('.', array_merge([$varName], $functions))));
        }

        return $objectValue;
    }
}
