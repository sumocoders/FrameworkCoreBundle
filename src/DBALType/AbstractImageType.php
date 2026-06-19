<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;

abstract class AbstractImageType extends Type
{
    /**
     * @param array $column
     * @param AbstractPlatform $platform
     *
     * @return string
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    /**
     * @param string $value
     * @param AbstractPlatform $platform
     *
     * @return AbstractImage|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->createFromString($value);
    }

    /**
     * @param AbstractImage $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value !== null ? (string) $value : null;
    }

    abstract protected function createFromString(string $imageName): ?AbstractImage;
}
