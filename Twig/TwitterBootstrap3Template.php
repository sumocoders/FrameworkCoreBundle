<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Pagerfanta\View\Template\TwitterBootstrapTemplate;

class TwitterBootstrap3Template extends TwitterBootstrapTemplate
{
    public function __construct()
    {
        parent::__construct();

        $this->setOptions(['active_suffix' => '<span class="sr-only">current page</span>']);
    }

    public function container(): string
    {
        return sprintf(
            '<nav aria-label="Page navigation"><ul class="%s">%%pages%%</ul></nav>',
            $this->option('css_container_class')
        );
    }
}
