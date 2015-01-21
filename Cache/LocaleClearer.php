<?php

namespace SumoCoders\FrameworkCoreBundle\Cache;

use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class LocaleClearer implements CacheClearerInterface
{
    public function clear($cacheDir)
    {
        // convert the cache dir in the web dir
        $webDir = $cacheDir . '/../../../web';

        $fs = new Filesystem();
        $finder = new Finder();

        // remove all files in /web/*/ that are called locale.json
        $finder->files()->in($webDir . '/*/');
        foreach ($finder as $file) {
            if ($file->getFilename() === 'locale.json') {
                $fs->remove($file->getPath() . '/' . $file->getFilename());
            }
        }
    }
}
