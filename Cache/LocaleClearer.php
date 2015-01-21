<?php

namespace SumoCoders\FrameworkCoreBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class LocaleClearer implements CacheClearerInterface
{
    private $rootDir;

    /**
     * Inject the kernel root directory
     */
    public function __construct($rootDir)
    {
        $this->rootDir = $rootDir;
    }

    /**
     * {@inheritdoc}
     */
    public function clear($cacheDir)
    {
        $fs = new Filesystem();
        $finder = new Finder();

        // remove all files in /web/*/ that are called locale.json
        $finder->files()->in($this->rootDir . '/../web/*/');
        foreach ($finder as $file) {
            if ($file->getFilename() === 'locale.json') {
                $fs->remove($file->getPath() . '/' . $file->getFilename());
            }
        }
    }
}
