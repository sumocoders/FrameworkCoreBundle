<?php

namespace SumoCoders\FrameworkCoreBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FrameworkExtension extends AbstractExtension
{
    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Get the registered functions
     *
     * @return array
     */
    public function getFunctions()
    {
        return [
            new TwigFunction(
                'bundleExists',
                [$this, 'bundleExists']
            ),
            new TwigFunction(
                'toTranslation',
                [$this, 'convertToTranslation']
            ),
            new TwigFunction(
                'ucfirst',
                'ucfirst'
            ),
        ];
    }

    /**
     * Check if a bundle exists
     *
     * @param string $bundle
     * @return bool
     */
    public function bundleExists($bundle)
    {
        $bundles = $this->container->getParameter('kernel.bundles');

        return array_key_exists($bundle, $bundles);
    }

    /**
     * Convert a given string into a string that will/can be used as a id for
     * translations
     *
     * @param string $stringToConvert
     * @return string
     */
    public function convertToTranslation($stringToConvert)
    {
        $stringToConvert = trim($stringToConvert);
        $stringToConvert = str_replace(
            ['_', '-', ' ', 'framework', 'Framework'],
            '.',
            $stringToConvert
        );

        // the first item will mostly be the prefix of the namespace
        $stringToConvert = preg_replace('/(.*)\.(.*)bundle/U', '$1$2', $stringToConvert);
        $stringToConvert = str_replace('bundle', '', $stringToConvert);
        $stringToConvert = str_replace('Bundle', '', $stringToConvert);

        if (strtolower(mb_substr($stringToConvert, 0, 11)) == 'sumocoders.') {
            $stringToConvert = substr($stringToConvert, 11);
        }

        // remove numbers if they appear at the end or as single items
        $stringToConvert = preg_replace('/\d+$/', '', $stringToConvert);
        $stringToConvert = preg_replace('/\.\d*\./', '.', $stringToConvert);

        $stringToConvert = preg_replace('/\.+/', '.', $stringToConvert);

        return trim($stringToConvert, '.');
    }
}
