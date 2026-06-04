# Encrypted fields

Transparently encrypts and decrypts a Doctrine column using libsodium (`sodium_crypto_secretbox`). The value is stored as `TEXT` in the database; PHP reads and writes a plain string. Encryption is field-level — the rest of the entity is not affected.

## Prerequisites

- PHP with the `sodium` extension (bundled since PHP 7.2)
- `ENCRYPTION_KEY` set in `.env.local` — a 64-character hex string (32 bytes)

Generate a key:

```bash
php -r "echo sodium_bin2hex(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES)) . PHP_EOL;"
```

Add to `.env.local`:

```dotenv
ENCRYPTION_KEY=your_64_char_hex_string_here
```

## Usage

Register the DBAL type in `config/packages/doctrine.yaml`:

```yaml
doctrine:
    dbal:
        types:
            encrypted: SumoCoders\FrameworkCoreBundle\DBALType\EncryptedDBALType
```

Use the `encrypted` type on any string property:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class User
{
    #[ORM\Column(type: 'encrypted', nullable: true)]
    private ?string $socialSecurityNumber = null;

    public function getSocialSecurityNumber(): ?string
    {
        return $this->socialSecurityNumber;
    }

    public function setSocialSecurityNumber(?string $value): void
    {
        $this->socialSecurityNumber = $value;
    }
}
```

## How it works

| Direction | Operation |
|-----------|-----------|
| Write (PHP → DB) | `sodium_crypto_secretbox` encrypts the value; stored as `hex(nonce)|hex(ciphertext)` |
| Read (DB → PHP) | Splits on `|`, decrypts with `sodium_crypto_secretbox_open`, returns plain string |

The nonce is randomly generated per write, so the same plaintext produces a different ciphertext each time.

## Limitations

- **Not searchable** — encrypted values cannot be used in `WHERE` clauses or indexes. Filter in PHP after fetching.
- **Type is always `TEXT`** — column length constraints have no effect.
- **String only** — the type stores and returns a string. Cast integers, dates, etc. in your entity getter/setter.
- **No key rotation built in** — changing `ENCRYPTION_KEY` requires re-encrypting all rows manually.

## Migrations

When adding an encrypted column to an existing table with data:

1. Add the nullable `encrypted` column
2. In a separate migration, read and re-save each row through the entity manager so the DBAL type encrypts the values
3. Apply any `NOT NULL` constraint in a third migration after the backfill

## Troubleshooting

- **`RuntimeException: ENCRYPTION_KEY should be a valid 64 character key`** — the env var is missing or not loaded. Check `.env.local` and restart the dev server.
- **`ConversionException` on read** — the stored value was encrypted with a different key, or the column contains a plain-text legacy value. Decrypt/migrate those rows before switching keys.
- **Column shows `(Encrypted)` comment in the database** — expected; the DBAL type sets that as the column comment automatically.
