<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Form\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Form\Type\FileType;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class UploadTypeEmptyDataTest extends TestCase
{
    /** @return iterable<string, array{AbstractType, array<string, string|null>}> */
    public static function uploadTypes(): iterable
    {
        yield 'image' => [new ImageType(), ['image_class' => 'App\ValueObject\Image', 'help' => null]];
        yield 'file' => [new FileType(), ['file_class' => 'App\ValueObject\File', 'help' => null]];
    }

    /** @param array<string, string|null> $options */
    #[DataProvider('uploadTypes')]
    public function testEmptyDataPlaceholderBehavesLikeAnEmptyUpload(AbstractType $type, array $options): void
    {
        $resolver = new OptionsResolver();
        $type->configureOptions($resolver);

        /** @var callable(): object $emptyData */
        $emptyData = $resolver->resolve($options)['empty_data'];
        $placeholder = $emptyData();

        // The widget templates read fileName to decide whether to show a preview
        if (!method_exists($placeholder, 'getFileName') || !method_exists($placeholder, 'getFile')) {
            static::fail('The empty_data placeholder must expose getFile() and getFileName()');
        }

        // A submit without a file reads the file, which must not be an uninitialised typed property
        static::assertNull($placeholder->getFile());
        static::assertNull($placeholder->getFileName());
    }
}
