<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

class Breadcrumb
{
    private string $title;
    private ?string $url;

    public function __construct(string $title, ?string $url = null)
    {
        $this->title = $title;
        $this->url = $url;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
}
