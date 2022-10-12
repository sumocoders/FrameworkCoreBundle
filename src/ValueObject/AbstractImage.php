<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

use Doctrine\ORM\Mapping as ORM;

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
    public const FALLBACK_IMAGE = null;

    public function getWebPath(): string
    {
        $webPath = parent::getWebPath();

        $file = $this->getAbsolutePath();

        if (is_file($file) && file_exists($file)) {
            return $webPath;
        }

        return static::FALLBACK_IMAGE;
    }

    public function getFallbackImage(): ?string
    {
        return static::FALLBACK_IMAGE;
    }
}
