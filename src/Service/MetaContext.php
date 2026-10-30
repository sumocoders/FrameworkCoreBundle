<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Service;

use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use Symfony\Contracts\Service\ResetInterface;

final class MetaContext implements ResetInterface
{
    public function __construct(
        private readonly PageTitle $pageTitle,
    ) {
    }

    private ?Meta $meta = null;

    public function setMeta(?Meta $meta): void
    {
        $this->meta = $meta;

        // Only one <title> is allowed
        $pageTitle = $meta?->getPageTitle();
        if ($pageTitle !== null && $pageTitle !== '') {
            $this->pageTitle->setTitle($pageTitle);
        }
    }

    public function getMeta(): ?Meta
    {
        return $this->meta;
    }

    public function reset(): void
    {
        $this->meta = null;
    }
}
