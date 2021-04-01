<?php

namespace SumoCoders\FrameworkCoreBundle\Annotation;

/**
 * @Annotation
 */
class Breadcrumb
{
    private ?string $title = null;
    private ?string $routeName = null;
    private ?string $parentRouteName = null;
    private array $routeParameters = [];
    private array $parentRouteParameters = [];

    public function __construct(array $data)
    {
        if (isset($data['value'])) {
            $this->title = $data['value'];
        }

        if (isset($data['title'])) {
            $this->title = $data['title'];
        }

        if (isset($data['route'])) {
            if (\is_array($data['route']) && isset($data['route']['name'])) {
                $this->routeName = $data['route']['name'];
            }
            if (\is_array($data['route']) && isset($data['route']['parameters'])) {
                $this->routeParameters = $data['route']['parameters'];
            }

            if (\is_string($data['route'])) {
                $this->routeName = $data['route'];
            }
        }

        if (isset($data['parent'])) {
            if (\is_array($data['parent']) && isset($data['parent']['name'])) {
                $this->parentRouteName = $data['parent']['name'];
            }
            if (\is_array($data['parent']) && isset($data['parent']['parameters'])) {
                $this->parentRouteParameters = $data['parent']['parameters'];
            }

            if (\is_string($data['parent'])) {
                $this->parentRouteName = $data['parent'];
            }
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getRouteName(): ?string
    {
        return $this->routeName;
    }

    public function getParentRouteName(): ?string
    {
        return $this->parentRouteName;
    }

    public function getRouteParameters(): array
    {
        return $this->routeParameters;
    }

    public function getParentRouteParameters(): array
    {
        return $this->parentRouteParameters;
    }
}
