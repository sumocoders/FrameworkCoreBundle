<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;

abstract class AbstractImageType extends Type
{
    /**
     * @param array $fieldDeclaration
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    // @phpstan-ignore missingType.iterableValue
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    /**
     * @param string $imageName
     * @param AbstractPlatform $platform
     *
     * @return AbstractImage|null
     */
    public function convertToPHPValue($imageName, AbstractPlatform $platform): ?AbstractImage
    {
        return $this->createFromString($imageName);
    }

    /**
     * @param AbstractImage $image
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue($image, AbstractPlatform $platform): ?string
    {
        // @phpstan-ignore notIdentical.alwaysTrue
        return $image !== null ? (string) $image : null;
    }

    abstract protected function createFromString(string $imageName): ?AbstractImage;
}
