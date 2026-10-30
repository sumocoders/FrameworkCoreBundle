<?php

declare(strict_types=1);

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Melbahja\Seo\Schema;
use Twig\Attribute\AsTwigFilter;

final readonly class SchemaExtension
{
    #[AsTwigFilter('schema_script', isSafe: ['html'])]
    public function schemaScript(Schema $schema): string
    {
        return (
            '<script type="application/ld+json">'
            . PHP_EOL
            . json_encode(
                $schema->jsonSerialize(),
                JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            )
            . PHP_EOL
            . '</script>'
        );
    }
}
