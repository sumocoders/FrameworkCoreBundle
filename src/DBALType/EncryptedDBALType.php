<?php

namespace SumoCoders\FrameworkCoreBundle\DBALType;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;

class EncryptedDBALType extends Type
{
    public const string ENCRYPTED = 'encrypted';

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return 'TEXT COMMENT \'(Encrypted)\'';
    }

    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!array_key_exists('ENCRYPTION_KEY', $_ENV) || trim($_ENV['ENCRYPTION_KEY']) === '') {
            throw new \RuntimeException('ENCRYPTION_KEY should be a valid 64 character key in your .env.local');
        }

        [$nonce, $encryptedValue] = explode('|', (string) $value);

        $decrypted = sodium_crypto_secretbox_open(
            sodium_hex2bin($encryptedValue),
            sodium_hex2bin($nonce),
            sodium_hex2bin($_ENV['ENCRYPTION_KEY']),
        );

        if ($decrypted === false) {
            throw new ConversionException(
                sprintf(
                    'Could not convert value to PHP value: \'%1$s\' for type \'%2$s\'.',
                    (string) $value,
                    $this->getName(),
                ),
            );
        }

        return $decrypted;
    }

    public function convertToDatabaseValue(mixed $value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if (!array_key_exists('ENCRYPTION_KEY', $_ENV) || trim($_ENV['ENCRYPTION_KEY']) === '') {
            throw new \RuntimeException('ENCRYPTION_KEY should be a valid 64 character key in your .env.local');
        }

        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $key = sodium_hex2bin($_ENV['ENCRYPTION_KEY']);

        $encryptedValue = sodium_crypto_secretbox((string) $value, $nonce, $key);

        return sodium_bin2hex($nonce) . '|' . sodium_bin2hex($encryptedValue);
    }

    public function getName(): string
    {
        return self::ENCRYPTED;
    }
}
