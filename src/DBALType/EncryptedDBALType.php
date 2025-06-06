<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

class EncryptedDBALType extends Type
{
    public const ENCRYPTED = 'encrypted';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'TEXT COMMENT \'(Encrypted)\'';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!isset($_ENV['ENCRYPTION_KEY'])) {
            throw new \RuntimeException('ENCRYPTION_KEY should be a valid 64 character key in your .env.local');
        }

        [$nonce, $encryptedValue] = explode('|', $value);

        $decrypted =  sodium_crypto_secretbox_open(
            sodium_hex2bin($encryptedValue),
            sodium_hex2bin($nonce),
            sodium_hex2bin($_ENV['ENCRYPTION_KEY'])
        );

        if ($decrypted === false) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $decrypted;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!isset($_ENV['ENCRYPTION_KEY'])) {
            throw new \RuntimeException('ENCRYPTION_KEY should be a valid 64 character key in your .env.local');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = sodium_hex2bin($_ENV['ENCRYPTION_KEY']);

        $encryptedValue = sodium_crypto_secretbox($value, $nonce, $key);

        return sodium_bin2hex($nonce) . '|' . sodium_bin2hex($encryptedValue);
    }

    public function getName(): string
    {
        return self::ENCRYPTED;
    }
}
