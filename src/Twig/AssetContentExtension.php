<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\Asset\Exception\AssetNotFoundException;
use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class AssetContentExtension
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
    ) {
    }

    #[AsTwigFunction('asset_content', isSafe: ['html'])]
    public function getAssetContent(string $path): ?string
    {
        $asset = $this->assetMapper->getAsset($path);

        if ($asset === null) {
            throw new AssetNotFoundException($path);
        }

        return $asset->content;
    }
}
