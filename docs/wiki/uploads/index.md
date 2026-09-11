[Back to index](../index.md)

# File & image uploads

A value-object + Doctrine-DBAL-type pairing that lets a project store an uploaded file as a single `VARCHAR(255)`
column (just the filename) while the value object itself carries the upload/delete lifecycle and the computed
filesystem/web paths. Nothing is upload-framework-specific — it's built directly on Doctrine lifecycle callbacks and
Symfony's `UploadedFile`.

Children: [Files](files.md), [Images](images.md).

## The pairing pattern

Every concrete upload is two classes a consuming project must write:

1. A **value object** extending `ValueObject\AbstractFile` (or `AbstractImage`), implementing `getUploadDir()`.
2. A **DBAL type** extending `DBALType\AbstractFileType` (or `AbstractImageType`), implementing `createFromString()`
   and `getName()`, registered under a project-chosen type name in `doctrine.yaml`.

```
Doctrine column (VARCHAR 255, the filename only)
   ⇅ AbstractFileType::convertToPHPValue / convertToDatabaseValue
Value object (AbstractFile subclass) — filename + optional pending UploadedFile
   ⇅ entity lifecycle callbacks (PrePersist/PreUpdate, PostPersist/PostUpdate, PostRemove)
Filesystem (public/files/<uploadDir>/<generatedName>.<ext>)
```

`AbstractFileType`/`AbstractImageType` (`src/DBALType/`) are thin: `getSQLDeclaration()` is hardcoded to
`VARCHAR(255)`, `convertToDatabaseValue()` just casts the VO to string (`AbstractFile::__toString()` returns the raw
filename), and `convertToPHPValue()` delegates to the subclass's `createFromString()` — which in practice is always
`SubclassName::fromString($fileName)`. The DBAL type never touches the filesystem; only the value object does.

## What a consuming project must implement

| Must provide | Where | Notes |
|---|---|---|
| `getUploadDir(): string` | value-object subclass | relative to `public/files/`; the abstract class trims leading/trailing slashes |
| `createFromString(string): ?AbstractFile` | DBAL-type subclass | in practice `SubclassName::fromString(...)` |
| `getName(): string` | DBAL-type subclass | the registered Doctrine type name |
| `#[ORM\HasLifecycleCallbacks]` + 3 callback methods | entity | see Workflow below — these are not optional |

## Workflow (entity lifecycle → filesystem)

`AbstractFile` (`src/ValueObject/AbstractFile.php`) does nothing on its own at persist time — the entity must forward
three lifecycle events to three methods:

| Doctrine event | VO method | Effect |
|---|---|---|
| `PrePersist`, `PreUpdate` | `prepareToUpload()` | no-op if no pending `UploadedFile`; otherwise generates a random filename (`sha1(uniqid(...))`, optionally slug-prefixed via `setNamePrefix()`) with the uploaded file's guessed extension, and sets `$fileName`. This only assigns the *name* — no bytes are written yet, so the DB column value is correct before the row exists on disk. |
| `PostPersist`, `PostUpdate` | `upload()` | deletes the old file first if one is being replaced (`oldFileName` set by `setFile()`), then moves the pending `UploadedFile` to `getUploadRootDir()` under the prepared filename. Runs **after** persist specifically so a move failure (which throws) doesn't leave a DB row referencing a file that was never written — but note the row *is* already committed by this point since `PostPersist` fires after flush; a failed move here does not roll back the DB write. |
| `PostRemove` | `remove()` | unlinks the file from disk if it exists. |

`setFile(UploadedFile $file)`: if a file already exists (`$fileName !== null`), it stashes the current name into
`oldFileName`, clears `$fileName`, and **returns a clone** of itself (not `$this`) — this is what lets Doctrine's
change tracking detect the property changed when a new file replaces an old one on the same entity.

`markForDeletion()` / `setPendingDeletion(true)` / `isPendingDeletion()`: used by `FileType`/`ImageType` (see
`docs/wiki/forms/index.md`) to let a form's "remove" checkbox null out `fileName` (via `oldFileName`) so `upload()`
deletes the file on next flush without a new one replacing it.

## Path resolution

- `getUploadRootDir()` = `'files/' . trim(getUploadDir(), '/\\')` — always rooted at `files/` relative to the
  working directory Doctrine's lifecycle callbacks run in (in practice `public/files/`).
- `getAbsolutePath()` = `getUploadRootDir() . '/' . fileName`, or `null` if no filename.
- `getWebPath()` (on `AbstractFile`) only returns a non-empty path if the file **actually exists on disk**
  (`is_file()` + `file_exists()` check) — a DB row with a filename but a missing file on disk silently renders as no
  file, not a broken link. `AbstractImage::getWebPath()` overrides this: see [Images](images.md).

## Consumption points

- `Form\Type\FileType` / `Form\Type\ImageType` (`src/Form/Type/`) — see `docs/wiki/forms/index.md` for how they
  bridge the compound form (`file` + `remove` sub-fields) to `fromUploadedFile()` / `setPendingDeletion()`.
- `jsonSerialize()` on `AbstractFile` returns `getWebPath()` — so any entity serialized to JSON (e.g. an API
  response) exposes the file as its public URL string, not its filename.
- `__toString()` returns the raw `fileName` (used by the DBAL type for persistence), **not** the web path.
  `docs/uploading-files.md` documents `__toString()` as returning the web path and its template example does
  `<a href="{{ user.document }}">` relying on that — this does not match current code (`return (string) $this->fileName;`
  in `src/ValueObject/AbstractFile.php`). Use `getWebPath()` explicitly in templates rather than relying on string
  coercion; treat the doc's claim as stale until reconciled.

## Edge cases

- `AbstractFile`'s constructor is `protected` — subclasses can only be built through `fromUploadedFile()` or
  `fromString()`, never `new SubclassName()` directly from outside.
- `prepareToUpload()` silently returns if there's no pending `UploadedFile` — calling it on an entity with no upload
  in progress is always safe (idempotent no-op), so wiring it unconditionally into `PrePersist`/`PreUpdate` on every
  save is the documented pattern, not a bug.
- File name generation has no collision check beyond `uniqid()`'s entropy — this bundle does not verify the target
  path is free before `move()`.
