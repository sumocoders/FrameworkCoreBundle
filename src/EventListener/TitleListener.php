<?php

namespace SumoCoders\FrameworkCoreBundle\EventListener;

use ReflectionClass;
use SumoCoders\FrameworkCoreBundle\Attribute\Title;
use SumoCoders\FrameworkCoreBundle\Service\Fallbacks;
use SumoCoders\FrameworkCoreBundle\Service\PageTitle;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class TitleListener
 *
 * This class is responsible for handling the title of the page.
 * It listens to the kernel controller event and sets the title based on the Title attribute.
 */
class TitleListener
{
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
        $controller = is_array($event->getController()) ? $event->getController()[0] : $event->getController();
        $methods = (new ReflectionClass($controller))->getMethods();

        // Loop through the methods and process the Title attributes
        foreach ($methods as $method) {
            $attributes = $method->getAttributes(Title::class, \ReflectionAttribute::IS_INSTANCEOF);

            if (empty($attributes)) {
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
                    $title .= $this->getTitleFromParent($titleAttribute->getParent(), $parameters);
                }

                $this->pageTitleService->setTitle($title . ' - ' . $this->fallbacks->get('site_title'));
            }
        }
    }

    /**
     * Process the parameters of a method.
     *
     * @param array<\ReflectionParameter> $reflextionParameters
     * @param array<mixed> $parameters
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
            if (empty($parameterAttributes)) {
                continue;
            }

            // Get the mapping and value of the parameter
            $mapping = $parameterAttributes[0]->getArguments()['mapping'] ?? null;
            $value = $mapping !== null && isset($parameters[$parameterName])
                ? $this->manager->getRepository($reflextionParameter->getType()->getName())->findOneBy([$mapping[$parameterName] => $parameters[$parameterName]])
                : $this->manager->getRepository($reflextionParameter->getType()->getName())->find($parameters[$parameterName]);

            $parameters[$parameterName] = $value;
        }

        return $parameters;
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
        foreach ($method->getAttributes(Title::class, \ReflectionAttribute::IS_INSTANCEOF) as $attribute) {
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
            $method = strpos($controller, '::') > 0 ? explode('::', $controller)[1] : '__invoke';

            // Get the required parameters of the route
            $requiredParameters = array_filter($route->compile()->getVariables(), fn($parameter) => $route->getDefault($parameter) === null);

            return [
                'controller' => $controller,
                'method' => $method,
                'parameters' => $requiredParameters,
            ];
        }

        return null;
    }
}
