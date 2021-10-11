<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Countable;
use Iterator;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;

class BreadcrumbTrail implements Iterator, Countable
{
    private int $index;
    private array $breadcrumbs;

    public function __construct()
    {
        $this->index = 0;
        $this->breadcrumbs = [];
    }

    public function reset(): void
    {
        $this->breadcrumbs = [];
    }

    public function add(Breadcrumb $breadcrumb): void
    {
        $this->breadcrumbs[] = $breadcrumb;
    }

    public function all(): array
    {
        return $this->breadcrumbs;
    }

    public function count(): int
    {
        return count($this->breadcrumbs);
    }

    public function current(): Breadcrumb
    {
        return $this->breadcrumbs[$this->index];
    }

    public function next(): void
    {
        ++ $this->index;
    }

    public function key(): int
    {
        return $this->index;
    }

    public function valid(): bool
    {
        return \array_key_exists($this->index, $this->breadcrumbs);
    }

    public function rewind(): void
    {
        $this->index = 0;
    }
}
