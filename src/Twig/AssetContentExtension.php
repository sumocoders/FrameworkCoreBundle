<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class AssetContentExtension
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
    ) {
    }

    #[AsTwigFunction('asset_content', isSafe: ['html'])]
    public function getAssetContent(string $path): string
    {
        return $this->assetMapper->getAsset($path)->content;
    }
}
