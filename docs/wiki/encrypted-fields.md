[Back to index](index.md)

# Encrypted fields

A Doctrine DBAL type that transparently encrypts a single string column at rest using libsodium secretbox
(authenticated symmetric encryption), so a project can mark one entity property as encrypted without touching any
query/entity code beyond the column type.

Primary code reference: `src/DBALType/EncryptedDBALType.php`

## Encrypt/decrypt flow

| Direction | `EncryptedDBALType` method | Steps |
|---|---|---|
| PHP → DB | `convertToDatabaseValue()` | generate a random nonce (`random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES)`) → `sodium_crypto_secretbox($value, $nonce, $key)` → store as `hex(nonce) . '|' . hex(ciphertext)` |
| DB → PHP | `convertToPHPValue()` | `explode('|', $value)` into nonce/ciphertext → `sodium_crypto_secretbox_open()` with the same key |

The key (`$_ENV['ENCRYPTION_KEY']`) is read via `sodium_hex2bin()` on every call — there is no in-request caching of
the decoded key. The nonce is regenerated on every write, so encrypting the same plaintext twice produces different
stored values (this also means the column can never be used in an equality `WHERE` clause or a unique index).

`getSQLDeclaration()` always returns `'TEXT COMMENT \'(Encrypted)\''` — column length options on `#[ORM\Column]` are
ignored, and the `(Encrypted)` comment is applied automatically as a visual marker in the schema.

## `ENCRYPTION_KEY` dependency

Both directions independently guard on `array_key_exists('ENCRYPTION_KEY', $_ENV) && trim(...) !== ''` and throw a
plain `\RuntimeException` if missing/blank — not a `ConversionException`. Expected to be a 64-character hex string
(32 raw bytes, `SODIUM_CRYPTO_SECRETBOX_KEYBYTES`) set in `.env.local`; there is no validation of the key's length or
hex-ness beyond what `sodium_hex2bin()` itself enforces at call time (a malformed key surfaces as a `SodiumException`
from `sodium_hex2bin()`, not the bundle's own `RuntimeException`).

## Failure modes

| Condition | What happens |
|---|---|
| `ENCRYPTION_KEY` unset/blank | `RuntimeException` on both read and write, before any sodium call |
| `ENCRYPTION_KEY` malformed hex | `SodiumException` from `sodium_hex2bin()`, uncaught |
| Stored value not in `nonce\|ciphertext` hex form (legacy plain-text row, truncated data) | `explode('|', ...)` either produces something `sodium_hex2bin()` rejects, or a nonce/ciphertext pair that fails authentication |
| Ciphertext fails authentication (wrong key, corrupted/tampered data, or the previous case) | `sodium_crypto_secretbox_open()` returns `false` → caught explicitly → thrown as Doctrine `ConversionException` (this is the *only* path that produces a `ConversionException`; everything else is a `RuntimeException` or an uncaught sodium exception) |

Because decrypt failures surface only as `ConversionException` (the other failures are `RuntimeException`/uncaught),
catching just `ConversionException` around entity reads will not catch a missing/malformed key.

## Edge cases

- **Not searchable/indexable**: the type is `TEXT` with a random nonce per write; any filtering must happen in PHP
  after fetching decrypted values, never in DQL/SQL `WHERE`.
- **String only**: `convertToDatabaseValue()`/`convertToPHPValue()` cast to/from `string`. Non-string scalars (ints,
  `DateTime`, etc.) need explicit casting in the entity's getter/setter — the type itself won't do it.
- **No key rotation**: changing `ENCRYPTION_KEY` makes every previously-encrypted row undecryptable
  (`ConversionException`). Rotation requires reading and re-saving every row under the old key before swapping it.
- Related but separate concern: `#[SensitiveData]` (entity-property attribute, see `docs/audit-trail.md`) masks a
  property's value in the audit log output — it does not encrypt anything. The two are commonly combined on the same
  property (encrypt at rest via this DBAL type, mask in the audit trail via the attribute) but are independent
  mechanisms with no code coupling between them.
