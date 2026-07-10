<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;

abstract class AbstractFileType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?AbstractFile
    {
        if (is_null($value)) {
            return null;
        }

        return $this->createFromString((string) $value);
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): ?string
    {
        return $value !== null ? (string) $value : null;
    }

    abstract protected function createFromString(string $fileName): ?AbstractFile;
}
