<?php

namespace SumoCoders\FrameworkCoreBundle\Serializer;

use Doctrine\ORM\Mapping\Id;

class MaxDepthHandler
{
    public function __invoke($innerObject, $outerObject, string $attributeName, string $format = null, array $context = [])
    {
        return $this->getId($innerObject);
    }

    private function getId(object $object): ?string
    {
        $reflectionClass = new \ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->getAttributes(Id::class)) {
                $property->setAccessible(true);
                return (string) $property->getValue($entity);
            }
        }

        return null;
    }
}
