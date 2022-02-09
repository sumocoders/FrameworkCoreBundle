<?php

namespace SumoCoders\FrameworkCoreBundle\DataTransferObject;

use SumoCoders\FrameworkCoreBundle\Exception\DataTransferExceptionException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class BaseDataTransferObject
{
    public static function from(object $entity): static
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyInfo = new PropertyInfoExtractor(
            [new ReflectionExtractor()]
        );

        // Read all public properties of the child DTO that extends this class
        $properties = $propertyInfo->getProperties(static::class);

        if (empty($properties)) {
            throw new DataTransferExceptionException(sprintf('Found no public properties in $%1s.', static::class));
        }

        // Create a new instance of our child DTO
        $dataTransferObject = new static();

        // For each public property, try to look for a getter in the entity
        foreach ($properties as $property) {
            $dataTransferObject->{$property} = $propertyAccessor->getValue($entity, $property);
        }

        return $dataTransferObject;
    }
}
