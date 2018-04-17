<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use SumoCoders\FrameworkCoreBundle\Twig\TwitterBootstrap3Template;
use Pagerfanta\View\TwitterBootstrapView;

class TwitterBootstrap3View extends TwitterBootstrapView
{
    protected function createDefaultTemplate()
    {
        return new TwitterBootstrap3Template();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'sumocoders';
    }
}
