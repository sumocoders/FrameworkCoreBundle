<?php

namespace SumoCoders\FrameworkCoreBundle\Service;

use Countable;
use Iterator;
use SumoCoders\FrameworkCoreBundle\ValueObject\Breadcrumb;

class BreadcrumbTrail implements Iterator, Countable
{
    private int $index;
    private array $breadcrumbs;
    private string $template;

    public function __construct(string $template)
    {
        $this->index = 0;
        $this->breadcrumbs = [];
        $this->template = $template;
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

    public function getTemplate(): string
    {
        return $this->template;
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
        // TODO: Implement rewind() method.
    }
}
