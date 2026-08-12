<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\AssetMapper\AssetMapperInterface;
use Twig\Attribute\AsTwigFunction;

final readonly class MailCssExtension
{
    public function __construct(
        private AssetMapperInterface $assetMapper,
    ) {
    }

    #[AsTwigFunction('mail_css', isSafe: ['html'])]
    public function getMailCss(): string
    {
        return $this->assetMapper->getAsset('styles/mail.scss')->content;
    }
}
