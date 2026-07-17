<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

/**
 * The following things are mandatory to use this class.
 *
 * You need to implement the method getUploadDir.
 * When using this class in an entity certain life cycle callbacks should be called
 * prepareToUpload for PrePersist() and PreUpdate()
 * upload for PostPersist() and PostUpdate()
 * remove for PostRemove()
 *
 * The following things are optional
 * A fallback image can be set by setting the full path of the image to the FALLBACK_IMAGE constant
 */
abstract class AbstractImage extends AbstractFile
{
    /**
     * @var string|null
     */
    public const ?string FALLBACK_IMAGE = null;

    public function getWebPath(): string
    {
        $webPath = parent::getWebPath();

        $file = $this->getAbsolutePath();

        if (!is_null($file) && is_file($file) && file_exists($file)) {
            return $webPath;
        }

        if ($this->getFallbackImage() === null) {
            throw new \RuntimeException('No fallback image set for ' . static::class);
        }

        // @mago-expect analysis:invalid-return-statement,nullable-return-statement
        return $this->getFallbackImage();
    }

    public function getFallbackImage(): ?string
    {
        return static::FALLBACK_IMAGE;
    }
}
