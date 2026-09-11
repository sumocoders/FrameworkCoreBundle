[Back to index](index.md)

# Files

`AbstractFile` is the base value object for any uploaded file (documents, PDFs, etc.) with no format assumptions.
`AbstractImage` (see [Images](images.md)) extends it with image-specific fallback behavior.

Primary code reference: `src/ValueObject/AbstractFile.php`

## API surface a subclass gets for free

| Method | Notes |
|---|---|
| `getFileName()` | raw stored filename or `null` |
| `getAbsolutePath()` / `getWebPath()` | see path resolution in [uploads index](index.md) |
| `hasFile()` | true only while an `UploadedFile` is pending (before `upload()` runs) |
| `getFile()` / `setFile()` | the pending `UploadedFile`; `setFile()` clones on replace (see index) |
| `setNamePrefix(string)` | prepends `Urlizer::urlize($prefix) . '_'` to the generated filename in `prepareToUpload()` |
| `markForDeletion()` / `setPendingDeletion()` / `isPendingDeletion()` | internal hooks used by `FileType` |
| `fromUploadedFile(?UploadedFile, ?string $namePrefix)` | static factory used by `Form\Type\FileType`'s model transformer |
| `fromString(?string $fileName)` | static factory used by `AbstractFileType::createFromString()` |

## Adding a new file type in a consuming project

1. Extend `AbstractFile`, implement `getUploadDir(): string` (relative to `public/files/`).
2. Extend `AbstractFileType`, implement `createFromString()` (→ `SubclassName::fromString($fileName)`) and
   `getName()`.
3. Register the DBAL type in `doctrine.yaml` under the chosen name.
4. Map the property `#[ORM\Column(type: '<name>', nullable: true)]` and wire the three lifecycle callback methods
   (`prepareToUpload`/`upload`/`remove`) on the entity with `#[ORM\PrePersist]`/`#[ORM\PreUpdate]`,
   `#[ORM\PostPersist]`/`#[ORM\PostUpdate]`, `#[ORM\PostRemove]` — the entity class also needs
   `#[ORM\HasLifecycleCallbacks]`.
5. Use `Form\Type\FileType` with `'file_class' => SubclassName::class`.

Full step-by-step with code: `docs/uploading-files.md`.

## Gotchas specific to files (not images)

- No fallback path — `getWebPath()` (inherited, not overridden) returns `''` if the file is missing on disk, unlike
  `AbstractImage` which can throw or fall back.
- `accept` form option defaults to `null` (any MIME type) on `FileType`, vs `'image/*'` on `ImageType`.
