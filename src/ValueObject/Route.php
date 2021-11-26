<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

class Route
{
    private string $name;
    private ?array $parameters;

    public function __construct(string $name, ?array $parameters)
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): ?array
    {
        return $this->parameters;
    }

    public function addParameters(array $parameters): void
    {
        $this->parameters = array_merge($this->parameters, $parameters);
    }
}
