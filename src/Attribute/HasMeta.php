<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final readonly class HasMeta
{
    public function __construct(
        public string $meta = 'getMeta',
        public string $slug = 'getSlug',
    ) {
    }
}
