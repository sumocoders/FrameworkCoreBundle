<?php

namespace SumoCoders\FrameworkCoreBundle\Serializer;

use Doctrine\ORM\Mapping\Id;

class CircularReferenceHandler
{
    public function __invoke($object)
    {
        return $this->getId($object);
    }

    private function getId(object $object): ?string
    {
        $reflectionClass = new \ReflectionClass($object);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property) {
            if ($property->getAttributes(Id::class)) {
                $property->setAccessible(true);
                return (string) $property->getValue($object);
            }
        }

        return null;
    }
}
