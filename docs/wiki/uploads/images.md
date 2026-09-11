[Back to index](index.md)

# Images

`AbstractImage` extends `AbstractFile` (see [Files](files.md)) with exactly one behavioral difference: a fallback
image path when no file has been uploaded (or the file is missing on disk).

Primary code reference: `src/ValueObject/AbstractImage.php`

## Fallback support

```php
abstract class AbstractImage extends AbstractFile
{
    public const ?string FALLBACK_IMAGE = null;

    public function getWebPath(): string { /* ... */ }
    public function getFallbackImage(): ?string { return static::FALLBACK_IMAGE; }
}
```

`getWebPath()` overrides the parent: it first tries `AbstractFile::getWebPath()` (which is `''` unless the file
exists on disk). If that's empty:

- if `FALLBACK_IMAGE` is set on the subclass, returns that path instead;
- if `FALLBACK_IMAGE` is **not** set (still `null`), **throws `RuntimeException`** (`'No fallback image set for ' .
  static::class`) — this is the one place `AbstractFile`/`AbstractImage` throws on a missing file rather than
  degrading gracefully. Every `AbstractImage` subclass either needs a real uploaded file at render time or a
  `FALLBACK_IMAGE` constant; there is no silent empty-string fallback like plain `AbstractFile` has.

`FALLBACK_IMAGE` must be an absolute public path (e.g. `/images/no-avatar.png`) — it's returned as-is, not resolved
relative to `getUploadDir()`.

## Adding a new image type in a consuming project

Same steps as [Files](files.md), extending `AbstractImage`/`AbstractImageType` instead, plus optionally overriding
`FALLBACK_IMAGE`. Full step-by-step: `docs/uploading-images.md`.

```php
final class UserAvatar extends AbstractImage
{
    public const FALLBACK_IMAGE = '/images/no-avatar.png';
    protected function getUploadDir(): string { return 'user/avatars'; }
}
```

## Edge cases

- No image processing of any kind lives in this bundle — no resizing, no format conversion, no EXIF stripping. A
  consuming project must do that itself, typically inside the entity's `prepareToUpload()`-adjacent lifecycle hook
  or a separate listener, before `upload()` moves the file to disk.
- Because `getWebPath()` can throw, calling it unconditionally in a template on an entity that might legitimately
  have neither an image nor a fallback will produce a 500, not a broken `<img>` tag — check `FALLBACK_IMAGE` is set
  for every `AbstractImage` subclass used in views, or guard the call.
- `Form\Type\ImageType` defaults `accept` to `'image/*'` and `preview_class` to `'img-thumbnail img-responsive'`
  (see `docs/forms.md` and `docs/wiki/forms/index.md`).
