# Encrypted fields

Transparently encrypts and decrypts a Doctrine column using libsodium (`sodium_crypto_secretbox`). The value is stored
as `TEXT` in the database; PHP reads and writes a plain string. Encryption is field-level, the rest of the entity is
not affected.

This is the bundle's own DBAL type and it stays the default choice. When you need to search an encrypted column, or
keep the keys in a real key management system, use Symfony's `KeyManagement` component instead — see
[Using symfony/key-management](#using-symfonykey-management).

## Which one to use

| Need                                                | Use                                      |
|-----------------------------------------------------|------------------------------------------|
| Encrypt a string column, one key in `.env.local`    | the bundle's `encrypted` DBAL type       |
| Search the column by exact value                    | `symfony/key-management` + a blind index |
| Keys in AWS KMS / Azure / GCP / Vault / Scaleway    | `symfony/key-management`                 |
| Key rotation without a hand-written re-encrypt pass | `symfony/key-management`                 |
| Encrypt a non-string column (date, json, ...)       | `symfony/key-management`                 |

Both can live in the same application: they are separate Doctrine types on separate columns.

## Prerequisites

- PHP with the `sodium` extension (bundled since PHP 7.2)
- `ENCRYPTION_KEY` set in `.env.local`: a 64-character hex string (32 bytes)

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

| Direction        | Operation                                                           |
|------------------|---------------------------------------------------------------------|
| Write (PHP → DB) | `sodium_crypto_secretbox` encrypts the value; stored as `hex(nonce)|hex(ciphertext)` |
| Read (DB → PHP)  | Splits on `|`, decrypts with `sodium_crypto_secretbox_open`, returns plain string |

The nonce is randomly generated per write, so the same plaintext produces a different ciphertext each time.

## Limitations

- **Not searchable**: encrypted values cannot be used in `WHERE` clauses or indexes. Filter in PHP after fetching,
  or use a blind index — see [Using symfony/key-management](#using-symfonykey-management).
- **Type is always `TEXT`**: column length constraints have no effect.
- **String only**: the type stores and returns a string. Cast integers, dates, etc. in your entity getter/setter.
- **No key rotation built in**: changing `ENCRYPTION_KEY` requires re-encrypting all rows manually. Rotation is
  handled for you by [symfony/key-management](#using-symfonykey-management).

## Migrations

When adding an encrypted column to an existing table with data:

1. Add the nullable `encrypted` column
2. In a separate migration, read and re-save each row through the entity manager so the DBAL type encrypts the values
3. Apply any `NOT NULL` constraint in a third migration after the backfill

## Using symfony/key-management

Symfony 8.2 ships a [KeyManagement component](https://symfony.com/doc/latest/key_management.html) that talks to a key
management system (AWS KMS, Azure Key Vault, Google Cloud KMS, HashiCorp Vault, Scaleway, KMIP) or to a local
libsodium backend, encrypts Doctrine columns through an attribute, and can make an encrypted column searchable
through a blind index.

> The component is marked **experimental** in Symfony 8.2 and is not covered by Symfony's backward compatibility
> promise. That is the main reason not to move everything over today — reach for it when you need a blind index or
> a real KMS, and keep the bundle's `encrypted` type everywhere else.

### Installation

```bash
composer require symfony/key-management
composer require symfony/doctrine-orm-key-management
```

The second package provides the `#[Encrypted]` and `#[BlindIndexed]` attributes and the ORM listener that fills the
index column. Each hosted provider is a separate package again (`symfony/aws-key-management`,
`symfony/hashicorp-vault-key-management`, ...); the local backends need nothing extra.

### Configuration

`KeyManagementBundle` is registered automatically. Declare one client per DSN:

```yaml
# config/packages/key_management.yaml
# https://symfony.com/doc/8.2/key_management.html
key_management:
    clients:
        default: 'sodium://?keys[default]=%env(KMS_DEFAULT_KEY)%'
```

`sodium://` is the local libsodium backend (XChaCha20-Poly1305), suitable for development and self-hosted setups.
`keys[default]` names the master key: `default` is the key id that `#[Encrypted('default')]` refers to, and the
value is 32 bytes in URL-safe base64. Generate one with:

```bash
php -r "echo sodium_bin2base64(sodium_crypto_aead_xchacha20poly1305_ietf_keygen(), SODIUM_BASE64_VARIANT_URLSAFE_NO_PADDING) . PHP_EOL;"
```

```dotenv
KMS_DEFAULT_KEY=your_base64_32_byte_key_here
```

Moving to a hosted KMS is a DSN change and a composer package: `aws-kms://default?region=eu-west-1`,
`sodium+dir:///etc/kms?ext=.bin`, and so on. See
[KMS Backends](https://symfony.com/doc/8.2/key_management/backends.html) for the DSN of each provider.

### Encrypting a column

Mark the property and name the key it is encrypted under:

```php
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\KeyManagement\Bridge\DoctrineOrm\Attribute\BlindIndexed;
use Symfony\Component\KeyManagement\Bridge\DoctrineOrm\Attribute\Encrypted;

#[ORM\Entity]
#[ORM\Index(columns: ['social_security_number_index'])]
class Person
{
    #[ORM\Column]
    #[Encrypted('default')]
    private(set) string $socialSecurityNumber;

    #[ORM\Column(length: 64)]
    #[BlindIndexed('socialSecurityNumber', 'default')]
    private string $socialSecurityNumberIndex = '';
}
```

| Detail                | Behaviour                                                                                      |
|-----------------------|------------------------------------------------------------------------------------------------|
| `#[Encrypted]` argument | The master key id, matching `keys[default]` in the DSN                                       |
| Column type           | Keeps its mapping; the value goes through that type first and is encrypted afterwards, so a `date_immutable` column stores an encrypted date |
| Column declaration    | Always an unbounded blob; `length` on `#[ORM\Column]` is ignored, because an envelope is larger than the value it holds |
| Authentication        | Each value is authenticated with its key and the wrapped Doctrine type name, so changing either means rewriting every row |
| `#[BlindIndexed]` arguments | First the source property, then the index name matching the `index:` attribute of the service tag |
| Index column          | 64 characters, because a tag is 32 bytes; nullable when the indexed property is nullable        |

The attribute goes on the column holding the tag, not on the source property, so one value can be indexed by
several columns under different indexes. The ORM listener fills the tag on insert and update only: rows written
before the index existed need a backfill script of your own.

### Searching an encrypted column (blind index)

An encrypted column cannot be searched — a fresh nonce on every write means `WHERE social_security_number = ?`
never matches. A blind index is a second column holding a keyed digest (a *tag*) of the value. Equal values give
equal tags, the tag is not reversible, and the database never holds the key.

**1. Generate the index key**

```bash
php bin/console key-management:generate-data-key default
```

The command prints a `wrapped` value and the `key_id` of the master key that wrapped it. Keep both:

```dotenv
KMS_DEFAULT_INDEX_KEY=the_base64_wrapped_value_the_command_printed
```

**2. Register one service per index**

```yaml
# config/services.yaml
services:
  app.blind_index.default:
    class: Symfony\Component\KeyManagement\BlindIndex
    arguments:
      $kms: '@key_management.default'
      $wrappedKey: !service
        class: Symfony\Component\KeyManagement\Ciphertext
        arguments:
          # the "wrapped" and "key_id" values the command printed
          - '%env(base64:KMS_DEFAULT_INDEX_KEY)%'
          - 'default'
      $name: 'default'
      $projection: !service
        class: Symfony\Component\KeyManagement\BlindIndex\Projection\Verbatim
    tags:
      - { name: 'key_management.blind_index', index: 'default' }
```

The *projection* decides which part of the value is indexed, on write and on search alike. It is a service rather
than a step at each call site because a tag written on a trimmed value and searched on an untrimmed one silently
matches nothing. Three ship with the component:

| Projection    | Indexes                                                       |
|---------------|---------------------------------------------------------------|
| `Verbatim`    | The value byte for byte — `Ada` and `ada` are two values      |
| `Email`       | An email address, folding the domain only (RFC 5321)          |
| `EmailDomain` | The domain alone, so "every account of that company" matches  |

**3. Query through the same index service**

A query has no entity to read the attribute on, so it computes the tag itself:

```php
<?php

namespace App\Repository;

use App\Entity\Person;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\KeyManagement\BlindIndexInterface;

final class PersonRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        #[Autowire(service: 'app.blind_index.default')]
        private readonly BlindIndexInterface $index,
    ) {
        parent::__construct($registry, Person::class);
    }

    public function findOneBySocialSecurityNumber(string $number): ?Person
    {
        return $this->findOneBy([
            'socialSecurityNumberIndex' => $this->index->of($number),
        ]);
    }
}
```

### Coming from the bundle's encrypted type

`#[Encrypted]` cannot read values written by `EncryptedDBALType`. The column goes from `TEXT` to a blob, and a
stored `nonce|ciphertext` string is not an envelope, so reading it fails.

Migrate by adding a second column with `#[Encrypted]`, reading every row through the old type and writing it back
through the new one in a migration of your own, then dropping the old column. Doing it in place leaves no way back
if the backfill stops halfway.

### Caveats

- The component is experimental in Symfony 8.2 and not covered by the backward compatibility promise.
- Equal values give equal tags, so anyone who can read the index column learns which rows share a value and how
  often each occurs. Index what has high entropy and is looked up by equality (an account number, an address);
  never a status, a country or a year of birth.
- A tag is not authenticated. Someone who can write to the database can change it.
- One tag gives exact equality only. Prefix, substring and range searches need several tags per row
  (`CoveringProjectionInterface` and `allOf()`), and `#[BlindIndexed]` refuses such an index rather than keeping
  one tag of several.
- `NULL` is stored as is, neither encrypted nor authenticated, and a value can still be copied to another row of
  the same column. When the value protects something (a two-factor secret), store the row identity inside the
  value and check it after decryption.

## Internals

- The key (`$_ENV['ENCRYPTION_KEY']`) is hex-decoded via `sodium_hex2bin()` on every call to
  `convertToDatabaseValue()`/`convertToPHPValue()` — there is no in-request caching of the decoded key.
- `getSQLDeclaration()` always returns `'TEXT COMMENT \'(Encrypted)\''`. Any length options set on the
  `#[ORM\Column]` attribute are ignored, and the `(Encrypted)` comment is applied automatically — this is why it
  shows up in the database schema.

### Failure modes

| Condition                                             | Result                                                                                 |
|--------------------------------------------------------|-----------------------------------------------------------------------------------------|
| `ENCRYPTION_KEY` unset/blank                          | `RuntimeException` on both read and write, before any sodium call                       |
| `ENCRYPTION_KEY` malformed hex                        | Uncaught `SodiumException` from `sodium_hex2bin()`                                       |
| Stored value not in `nonce\|ciphertext` hex form      | `explode()`/`sodium_hex2bin()` rejects it, or the pair fails authentication              |
| Ciphertext fails authentication                       | `sodium_crypto_secretbox_open()` returns `false`, caught and rethrown as `ConversionException` |

Only the last row produces a `ConversionException` — catching just that exception type will not catch the
missing/malformed-key cases above it.

## Troubleshooting

- **`RuntimeException: ENCRYPTION_KEY should be a valid 64 character key`**: the env var is missing or not loaded.
  Check `.env.local` and restart the dev server.
- **`ConversionException` on read**: the stored value was encrypted with a different key, or the column contains a
  plain-text legacy value. Decrypt/migrate those rows before switching keys.
- **Column shows `(Encrypted)` comment in the database**: expected; the DBAL type sets that as the column comment
  automatically.
- **Decryption fails right after adding `#[Encrypted]` to a populated column**: the existing values are not
  envelopes. Adding the attribute does not encrypt what the column already holds — backfill it first, see
  [Coming from the bundle's encrypted type](#coming-from-the-bundles-encrypted-type).
- **A blind index search matches nothing**: either the rows were written before the index existed (the ORM
  listener only fills tags on insert and update), or the search presents the value in a different form than the
  projection expects — `Verbatim` is case- and whitespace-sensitive.
