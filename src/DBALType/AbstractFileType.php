<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractFile;

abstract class AbstractFileType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform): string
    {
        return 'VARCHAR(255)';
    }

    /**
     * @param string $fileName
     * @param AbstractPlatform $platform
     *
     * @return AbstractFile|null
     */
    public function convertToPHPValue($fileName, AbstractPlatform $platform): ?AbstractFile
    {
        return $this->createFromString($fileName);
    }

    /**
     * @param AbstractFile $file
     * @param AbstractPlatform $platform
     *
     * @return string|null
     */
    public function convertToDatabaseValue($file, AbstractPlatform $platform): ?string
    {
        return $file !== null ? (string) $file : null;
    }

    abstract protected function createFromString(string $fileName): ?AbstractFile;
}
