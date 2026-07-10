<?php

namespace SumoCoders\FrameworkCoreBundle\Serializer;

use Doctrine\ORM\Mapping\Id;

class MaxDepthHandler
{
    // @mago-expect analysis:unused-parameter
    public function __invoke(
        object $innerObject,
        object $outerObject,
        string $attributeName,
        ?string $format = null,
        // @mago-expect analysis:imprecise-type
        array $context = [],
    ): ?string {
        return $this->getId($innerObject);
    }

    private function getId(object $object): ?string
    {
        $reflectionClass = new \ReflectionClass($object);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->getAttributes(Id::class)) {
                return (string) $property->getValue($object);
            }
        }

        return null;
    }
}
