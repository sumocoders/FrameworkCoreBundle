# Encrypted strings in the database

The core bundle has a DBAL type which encrypts strings with the built-in PHP libsodium functions. To use it, simply
apply the type to a string in your entity.

Example:

```php
    /**
     * @ORM\Column(type="encrypted")
     */
    private string $encryptedString;
```

