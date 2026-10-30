<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Twig;

use Melbahja\Seo\Schema;
use Melbahja\Seo\Schema\CreativeWork;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Twig\SchemaExtension;
use ReflectionMethod;
use Twig\Attribute\AsTwigFilter;

final class SchemaExtensionTest extends TestCase
{
    private SchemaExtension $extension;

    protected function setUp(): void
    {
        $this->extension = new SchemaExtension();
    }

    public function testExposesSchemaScriptFilter(): void
    {
        $attributes = new ReflectionMethod(SchemaExtension::class, 'schemaScript')->getAttributes(AsTwigFilter::class);

        static::assertCount(1, $attributes);
        static::assertSame('schema_script', $attributes[0]->newInstance()->name);
    }

    public function testOutputContainsScriptTag(): void
    {
        $schema = new Schema(new CreativeWork(props: ['name' => 'Test'], type: 'BlogPosting'));

        $output = $this->extension->schemaScript($schema);

        static::assertStringContainsString('<script type="application/ld+json">', $output);
        static::assertStringContainsString('</script>', $output);
    }

    public function testOutputIsPrettyPrinted(): void
    {
        $schema = new Schema(new CreativeWork(props: ['name' => 'Test'], type: 'BlogPosting'));

        $output = $this->extension->schemaScript($schema);

        static::assertStringContainsString(PHP_EOL, $output);
    }

    public function testOutputContainsSchemaData(): void
    {
        $schema = new Schema(new CreativeWork(props: ['name' => 'My Post'], type: 'BlogPosting'));

        $output = $this->extension->schemaScript($schema);

        static::assertStringContainsString('BlogPosting', $output);
        static::assertStringContainsString('My Post', $output);
        static::assertStringContainsString('schema.org', $output);
    }
}
