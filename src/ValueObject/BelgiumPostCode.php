<?php

namespace SumoCoders\FrameworkCoreBundle\ValueObject;

readonly class BelgiumPostCode
{
    public function __construct(
        public string $postcode,
        public string $municipality,
    ) {
    }
}
