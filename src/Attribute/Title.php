<?php

namespace SumoCoders\FrameworkCoreBundle\Attribute;

use Attribute;
use SumoCoders\FrameworkCoreBundle\ValueObject\Route;

#[Attribute(Attribute::TARGET_METHOD)]
final class Title
{
    private string $title;
    private ?Route $parent;

    private bool $extend = true;

    public function __construct(
        string $title,
        ?array $parent = null,
        $extend = true,
    ) {
        $this->title = $title;

        if ($parent !== null) {
            $this->parent = new Route(
                $parent['name'],
                \array_key_exists('parameters', $parent) ? $parent['parameters'] : null
            );
        } else {
            $this->parent = $parent;
        }
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function isExtend(): bool
    {
        return $this->extend;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function getParent(): ?Route
    {
        return $this->parent;
    }
}
