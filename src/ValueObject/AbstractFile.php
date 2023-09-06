<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation\Slug;
use Gedmo\Mapping\Annotation\SlugHandler;
use Gedmo\Sluggable\Sluggable;
use Gedmo\Sluggable\SluggableListener;
use Gedmo\Sluggable\Util\Urlizer;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * The following things are mandatory to use this class.
 *
 * You need to implement the method getUploadDir.
 * When using this class in an entity certain life cycle callbacks should be called
 * prepareToUpload for PrePersist() and PreUpdate()
 * upload for PostPersist() and PostUpdate()
 * remove for PostRemove()
 */
abstract class AbstractFile
{
    protected ?UploadedFile $file = null;

    protected ?string $oldFileName = null;

    protected ?string $namePrefix = null;

    protected function __construct(
        #[ORM\Column(type: 'string', length: 255, nullable: true)]
        protected ?string $fileName = null,
    ) {
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getAbsolutePath(): ?string
    {
        return $this->fileName === null ? null : $this->getUploadRootDir() . '/' . $this->fileName;
    }

    public function getWebPath(): string
    {
        $file = $this->getAbsolutePath();
        if (is_file($file) && file_exists($file)) {
            return '/files/' . $this->getUploadDir() . '/' . $this->fileName;
        }

        return '';
    }

    protected function getUploadRootDir(): string
    {
        // the absolute directory path where uploaded documents should be saved
        return 'files/' . $this->getTrimmedUploadDir();
    }

    protected function getTrimmedUploadDir(): string
    {
        return trim($this->getUploadDir(), '/\\');
    }

    /**
     * The dir in the public folder where the file needs to be uploaded.
     * The base directory is always the public/files directory
     *
     * @return string
     */
    abstract protected function getUploadDir(): string;

    public function setFile(UploadedFile $file = null): self
    {
        if ($file === null) {
            return $this;
        }

        $this->file = $file;
        // check if we have an old file path
        if ($this->fileName === null) {
            return $this;
        }

        // store the old name to delete after the update
        $this->oldFileName = $this->fileName;
        $this->fileName = null;

        return clone $this;
    }

    public static function fromUploadedFile(
        ?UploadedFile $uploadedFile = null,
        ?string $namePrefix = null
    ): static {
        $file = new static(null);
        $file->setFile($uploadedFile);
        if ($namePrefix !== null) {
            $file->setNamePrefix($namePrefix);
        }

        return $file;
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    final public function hasFile(): bool
    {
        return $this->file instanceof UploadedFile;
    }

    /**
     * This function should be called for the life cycle events PrePersist() and PreUpdate()
     */
    public function prepareToUpload(): void
    {
        if ($this->getFile() === null) {
            return;
        }

        // do whatever you want to generate a unique name
        $filename = sha1(uniqid(mt_rand(), true));
        if ($this->namePrefix !== null) {
            $filename = Urlizer::urlize($this->namePrefix) . '_' . $filename;
        }
        $this->fileName = $filename . '.' . $this->getFile()->guessExtension();
    }

    /**
     * This function should be called for the life cycle events PostPersist() and PostUpdate()
     */
    public function upload(): void
    {
        // check if we have an old image
        if ($this->oldFileName !== null) {
            $this->removeOldFile();
        }

        if (!$this->hasFile()) {
            return;
        }

        $this->writeFileToDisk();

        $this->file = null;
    }



    /**
     * This will remove the old file, can be extended to add extra functionality
     */
    protected function removeOldFile(): void
    {
        // delete the old file
        $oldFile = $this->getUploadRootDir() . '/' . $this->oldFileName;
        if (is_file($oldFile) && file_exists($oldFile)) {
            unlink($oldFile);
        }

        $this->oldFileName = null;
    }

    /**
     * if there is an error when moving the file, an exception will
     * be automatically thrown by move(). This will properly prevent
     * the entity from being persisted to the database on error
     */
    protected function writeFileToDisk(): void
    {
        $this->getFile()->move($this->getUploadRootDir(), $this->fileName);
    }

    /**
     * This function should be called for the life cycle event PostRemove()
     */
    public function remove(): void
    {
        $file = $this->getAbsolutePath();
        if (!is_file($file) || !file_exists($file)) {
            return;
        }

        unlink($file);
    }

    public function __toString(): string
    {
        return (string) $this->fileName;
    }

    public static function fromString(?string $fileName): ?self
    {
        return $fileName !== null ? new static($fileName) : null;
    }

    /**
     * The next time doctrine saves this to the database the file will be removed
     */
    public function markForDeletion(): void
    {
        $this->oldFileName = $this->fileName;
        $this->fileName = null;
    }

    /**
     * @param string $namePrefix If set this will be prepended to the generated filename
     *
     * @return self
     */
    public function setNamePrefix(string $namePrefix): self
    {
        $this->namePrefix = $namePrefix;

        return $this;
    }

    /**
     * @internal Used by the form types
     *
     * @param bool $isPendingDeletion
     */
    public function setPendingDeletion($isPendingDeletion): void
    {
        if ($isPendingDeletion) {
            $this->markForDeletion();
        }
    }

    /**
     * @internal Used by the form types
     *
     * @return bool
     */
    public function isPendingDeletion(): bool
    {
        return \strlen($this->oldFileName) > 0 && $this->fileName === null;
    }

    public function jsonSerialize(): string
    {
        return $this->getWebPath();
    }
}
