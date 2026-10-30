<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Twig;

use SumoCoders\FrameworkCoreBundle\Entity\Meta;
use SumoCoders\FrameworkCoreBundle\Service\MetaContext;
use Twig\Attribute\AsTwigFunction;

final readonly class MetaExtension
{
    public function __construct(
        private MetaContext $metaContext,
    ) {
    }

    #[AsTwigFunction('meta_tags', isSafe: ['html'])]
    public function metaTags(): string
    {
        $meta = $this->metaContext->getMeta();
        if (!$meta instanceof Meta) {
            return '';
        }

        return $meta->getMetaTagsInstance()->__toString();
    }
}
