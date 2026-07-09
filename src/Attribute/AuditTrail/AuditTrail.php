<?php

namespace SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class AuditTrail
{
    // @phpstan-ignore missingType.iterableValue
    public function __construct(
        public array $fields = [],
        public bool $withData = true,
    ) {
    }
}
