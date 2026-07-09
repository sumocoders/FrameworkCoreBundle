<?php

namespace SumoCoders\FrameworkCoreBundle\Serializer;

use Doctrine\ORM\Mapping\Id;

class MaxDepthHandler
{
    public function __invoke(
        $innerObject,
        $outerObject,
        string $attributeName,
        ?string $format = null,
        array $context = [],
    ): ?string {
        return $this->getId($innerObject);
    }

    private function getId(object $object): ?string
    {
        $reflectionClass = new \ReflectionClass($object);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            // @mago-expect lint:prefer-early-continue
            if ($property->getAttributes(Id::class)) {
                $property->setAccessible(true);

                return (string) $property->getValue($object);
            }
        }

        return null;
    }
}
