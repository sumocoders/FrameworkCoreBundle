<?php

declare(strict_types=1);

namespace SumoCoders\tests\Unit\Form\Type;

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use SumoCoders\FrameworkCoreBundle\DataTransferObject\SeoDataTransferObject;
use SumoCoders\FrameworkCoreBundle\Enum\RobotsDirective;
use SumoCoders\FrameworkCoreBundle\Form\Type\SeoType;
use Symfony\Component\Form\FormExtensionInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;

#[AllowMockObjectsWithoutExpectations]
final class SeoTypeTest extends TypeTestCase
{
    /** @return list<FormExtensionInterface> */
    protected function getExtensions(): array
    {
        return [
            ...parent::getExtensions(),
            new PreloadedExtension([new SeoType()], []),
        ];
    }

    public function testSubmitWithOverrideCheckedStoresSubmittedValues(): void
    {
        $form = $this->factory->create(SeoType::class);

        $form->submit([
            'overridePageTitle' => '1',
            'pageTitle' => 'My title',
            'overrideMetaDescription' => '1',
            'metaDescription' => 'My description',
            'robotsIndex' => RobotsDirective::NoIndex->value,
            'robotsFollow' => RobotsDirective::Follow->value,
            'robotsAdditional' => [RobotsDirective::NoArchive->value],
        ]);

        static::assertTrue($form->isSynchronized());

        /** @var SeoDataTransferObject $data */
        $data = $form->getData();
        static::assertInstanceOf(SeoDataTransferObject::class, $data);
        static::assertTrue($data->overridePageTitle);
        static::assertSame('My title', $data->pageTitle);
        static::assertTrue($data->overrideMetaDescription);
        static::assertSame('My description', $data->metaDescription);
        static::assertSame(RobotsDirective::NoIndex, $data->robotsIndex);
        static::assertSame(RobotsDirective::Follow, $data->robotsFollow);
        static::assertSame([RobotsDirective::NoArchive], $data->robotsAdditional);
    }

    public function testSubmitWithNothingSelectedLeavesEverythingEmpty(): void
    {
        $form = $this->factory->create(SeoType::class);

        $form->submit([
            'pageTitle' => '',
            'metaDescription' => '',
            'robotsAdditional' => [],
        ]);

        static::assertTrue($form->isSynchronized());

        /** @var SeoDataTransferObject $data */
        $data = $form->getData();
        static::assertFalse($data->overridePageTitle);
        static::assertFalse($data->overrideMetaDescription);
        static::assertNull($data->robotsIndex);
        static::assertNull($data->robotsFollow);
        static::assertSame([], $data->robotsAdditional);
    }

    public function testPageTitleSourceSelectorOptionIsPassedToView(): void
    {
        $form = $this->factory->create(SeoType::class, null, [
            'page_title_source_selector' => '[data-seo-title-field]',
        ]);

        $view = $form->createView();

        static::assertSame('[data-seo-title-field]', $view->vars['page_title_source_selector']);
    }

    public function testPageTitleSourceSelectorDefaultsToEmptyString(): void
    {
        $form = $this->factory->create(SeoType::class);

        $view = $form->createView();

        static::assertSame('', $view->vars['page_title_source_selector']);
    }
}
