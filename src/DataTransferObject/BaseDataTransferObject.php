<?php

namespace App\DataTransferObject;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;

class BaseDataTransferObject
{
    protected object $source;

    final public function __construct(?object $source = null)
    {
        if ($source === null) {
            return;
        }

        $this->source = $source;

        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $propertyInfo = new PropertyInfoExtractor(
            [new ReflectionExtractor()],
            [new ReflectionExtractor()],
            [],
            [new ReflectionExtractor()],
        );

        // Read all public properties of the child DTO that extends this class
        $properties = $propertyInfo->getProperties(static::class);

        foreach ($properties as $property) {
            // Only continue if the property is writable (a.k.a. public)
            if ($propertyInfo->isWritable(static::class, $property)) {
                // Get the value from the passed source object
                $sourceValue = $propertyAccessor->getValue($source, $property);

                // Get the property type & class name
                $type = $propertyInfo->getTypes(static::class, $property)[0];
                $className = $type->getClassName();

                // If the property is a class that extends this class, handle it recursively
                if ($className !== null && is_subclass_of($className, self::class)) {
                    $this->{$property} = new $className($sourceValue);
                } else {
                    $this->{$property} = $sourceValue;
                }
            }
        }
    }

    public function getSource(): object
    {
        return $this->source;
    }
}
