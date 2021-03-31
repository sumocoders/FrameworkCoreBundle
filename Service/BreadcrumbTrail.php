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

    public function getBreadcrumbs(): array
    {
        return $this->breadcrumbs;
    }

    public function count()
    {
        return count($this->breadcrumbs);
    }

    public function current()
    {
        return $this->breadcrumbs[$this->index];
    }

    public function next()
    {
        ++ $this->index;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return isset($this->breadcrumbs[$this->index]);
    }

    public function rewind()
    {
        $this->index = 0;
    }
}
