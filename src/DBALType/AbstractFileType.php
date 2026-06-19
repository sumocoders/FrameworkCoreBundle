<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;

abstract class AbstractFileType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    /**
     * @param string $value
     * @param AbstractPlatform $platform
     *
     * @return AbstractFile|null
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $this->createFromString($value);
    }

    /**
     * @param AbstractFile $value
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        return $value !== null ? (string) $value : null;
    }

    abstract protected function createFromString(string $fileName): ?AbstractFile;
}
