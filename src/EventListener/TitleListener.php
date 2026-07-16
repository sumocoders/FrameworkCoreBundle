<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionMethod;
use SumoCoders\FrameworkCoreBundle\Attribute\Title;
use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class TitleListener
 *
 * This class is responsible for handling the title of the page.
 * It listens to the kernel controller event and sets the title based on the Title attribute.
 */
// @mago-expect lint:kan-defect,cyclomatic-complexity
class TitleListener
{
    // @mago-expect lint:excessive-parameter-list
    public function __construct(
        private PageTitle $pageTitleService,
        private Fallbacks $fallbacks,
        private RouterInterface $router,
        private TranslatorInterface $translator,
        private EntityManagerInterface $manager,
        private PropertyAccessorInterface $propertyAccess,
    ) {
    }

    /**
     * Event listener for the kernel controller event.
     *
     * @param KernelEvent $event
     */
    public function onKernelController(KernelEvent $event): void
    {
        // Get the controller and its methods
        // @mago-expect analysis:non-existent-method(3),mixed-assignment
        $controller = is_array($event->getController()) ? $event->getController()[0] : $event->getController();
        // @mago-expect analysis:mixed-argument
        $methods = new ReflectionClass($controller)->getMethods();

        // Loop through the methods and process the Title attributes
        foreach ($methods as $method) {
            $attributes = $this->getTitleAttributes($method);

            if (count($attributes) === 0) {
                continue;
            }

            // Process the parameters of the method
            $parameters = $this->processParameters($method->getParameters(), $event->getRequest()->attributes->all());

            // Loop through the Title attributes and set the page title
            foreach ($attributes as $attribute) {
                $titleAttribute = $attribute->newInstance();

                if (!$titleAttribute->isExtend()) {
                    $this->pageTitleService->setTitle($titleAttribute->getTitle());

                    return;
                }

                $title = $this->processTitle($titleAttribute->getTitle(), $parameters);

                if ($titleAttribute->hasParent()) {
                    // @mago-expect analysis:possibly-null-argument
                    $title .= $this->getTitleFromParent($titleAttribute->getParent(), $parameters);
                }

                // @mago-expect analysis:mixed-operand
                $this->pageTitleService->setTitle($title . ' - ' . $this->fallbacks->get('site_title'));
            }
        }
    }

    /**
     * Process the parameters of a method.
     *
     * @param array<\ReflectionParameter> $reflextionParameters
     * @param array<mixed>                $parameters
     * @return array<mixed>
     */
    private function processParameters(array $reflextionParameters, array $parameters): array
    {
        // Loop through the reflection parameters and process the MapEntity attributes
        foreach ($reflextionParameters as $reflextionParameter) {
            $parameterName = $reflextionParameter->getName();

            if (!array_key_exists($parameterName, $parameters)) {
                continue;
            }

            $parameterAttributes = $reflextionParameter->getAttributes(MapEntity::class);
            if (count($parameterAttributes) === 0) {
                continue;
            }

            // Get the mapping and value of the parameter
            // @mago-expect analysis:mixed-assignment
            $mapping = $parameterAttributes[0]->getArguments()['mapping'] ?? null;
            // @mago-expect lint:no-else-clause
            if ($mapping !== null && $parameters[$parameterName] !== null) {
                // @mago-expect analysis:possible-method-access-on-null,non-existent-method(2),mixed-argument,mixed-array-access,invalid-array-element-key,less-specific-argument
                $value = $this->manager
                    ->getRepository($reflextionParameter->getType()->getName())
                    ->findOneBy([$mapping[$parameterName] => $parameters[$parameterName]]);
            } else {
                // @mago-expect analysis:possible-method-access-on-null,non-existent-method(2),mixed-argument
                $value = $this->manager
                    ->getRepository($reflextionParameter->getType()->getName())
                    ->find($parameters[$parameterName]);
            }

            $parameters[$parameterName] = $value;
        }

        return $parameters;
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
        // @mago-expect analysis:possibly-null-array-access,mixed-argument
        $class = new \ReflectionClass($routeInformation['controller']);
        // @mago-expect analysis:mixed-argument
        $method = $class->getMethod($routeInformation['method'] ?? '__invoke');

        $title = '';
        // Loop through the Title attributes of the method and process them
        foreach ($this->getTitleAttributes($method) as $attribute) {
            $parentAttribute = $attribute->newInstance();
            $title .= ' - ' . $this->processTitle($parentAttribute->getTitle(), $parameters);

            if ($parentAttribute->hasParent()) {
                // @mago-expect analysis:possibly-null-argument
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
        if (str_contains($title, '{')) {
            $matches = [];
            preg_match_all('/\{(.*?)\}/', $title, $matches);

            foreach ($matches[1] as $match) {
                $parts = explode('.', $match);

                if (!array_key_exists($parts[0], $parameters)) {
                    throw new \Exception(sprintf('Parameter %s not found in request', $parts[0]));
                }

                // @mago-expect analysis:mixed-assignment,mixed-argument
                $replaceWith = count($parts) === 2
                    ? $this->propertyAccess->getValue($parameters[$parts[0]], $parts[1])
                    : $parameters[$parts[0]];

                // @mago-expect analysis:mixed-argument
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
            // @mago-expect analysis:mixed-assignment
            $controller = $route->getDefault('_controller');
            // @mago-expect analysis:mixed-argument(2)
            $method = str_contains($controller, '::') ? explode('::', $controller)[1] : '__invoke';

            // Get the required parameters of the route
            $requiredParameters = array_filter(
                $route->compile()->getVariables(),
                static fn (string $parameter): bool => $route->getDefault($parameter) === null,
            );

            return [
                'controller' => $controller,
                'method' => $method,
                'parameters' => $requiredParameters,
            ];
        }

        return null;
    }
}
