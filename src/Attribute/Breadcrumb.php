<?php

namespace SumoCoders\FrameworkCoreBundle\Attribute;

use Attribute;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
final class Breadcrumb
{
    private string $title;
    private array $parameters;
    private ?Route $route;
    private ?Route $parent;

    public function __construct(
        string $title,
        ?array $parameters = null,
        ?array $route = null,
        ?array $parent = null,
    ) {
        $this->title = $title;

        // @mago-expect lint:no-else-clause
        if ($route !== null) {
            $this->route = new Route(
                $route['name'],
                \array_key_exists('parameters', $route) ? $route['parameters'] : null,
            );
        } else {
            $this->route = $route;
        }

        // @mago-expect lint:no-else-clause
        if ($parent !== null) {
            $this->parent = new Route(
                $parent['name'],
                \array_key_exists('parameters', $parent) ? $parent['parameters'] : null,
            );
        } else {
            $this->parent = $parent;
        }

        // @mago-expect lint:no-else-clause
        if ($parameters !== null) {
            $this->parameters = $parameters;
        } else {
            $this->parameters = [];
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getRoute(): ?Route
    {
        return $this->route;
    }

    public function setRoute(Route $route): void
    {
        $this->route = $route;
    }

    public function getParent(): ?Route
    {
        return $this->parent;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function hasRoute(): bool
    {
        return $this->route !== null;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }
}
