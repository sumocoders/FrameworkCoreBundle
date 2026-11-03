<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Form\Type;

use PHPUnit\Framework\TestCase;
use SumoCoders\FrameworkCoreBundle\Form\Type\ImageType;
use SumoCoders\FrameworkCoreBundle\ValueObject\AbstractImage;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

final class ImageTypeBuildViewTest extends TestCase
{
    public function testImageWithoutFileAndFallbackHasNoPreview(): void
    {
        // An empty submit becomes an image without a file; without a fallback getWebPath() would throw
        $image = new class extends AbstractImage {
            public function __construct()
            {
                parent::__construct();
            }

            protected function getUploadDir(): string
            {
                return 'images';
            }
        };
        $form = $this->createStub(FormInterface::class);
        $form->method('getData')->willReturn($image);
        $view = new FormView();

        new ImageType()->buildView($view, $form, [
            'show_preview' => true,
            'show_remove_image' => true,
            'required' => false,
        ]);

        static::assertFalse($view->vars['preview_url']);
    }
}
