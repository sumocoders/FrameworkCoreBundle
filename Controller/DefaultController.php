<?php

namespace SumoCoders\FrameworkCoreBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\Response;

use JMS\TranslationBundle\Model\MessageCatalogue;

class DefaultController extends Controller
{
    /**
     * @Route("/locale.json")
     */
    public function generateLocaleAction(Request $request)
    {
        $catalogue = new MessageCatalogue();
        $loader = $this->get('jms_translation.loader_manager');

        // load external resources, so current translations can be reused in the final translation
        foreach ($this->retrieveDirs() as $resource) {
            $catalogue->merge($loader->loadFromDirectory(
                $resource,
                $request->getLocale()
            ));
        }

        $localesArray = array();
        foreach ($catalogue->getDomains() as $domain => $collection) {
            foreach ($collection->all() as $key => $translation) {
                $localesArray[$key] =  $translation->getLocaleString();
            }
        }

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $response->setContent(json_encode($localesArray));

        return $response;
    }

    /**
     * The following methods is derived from code of the FrameworkExtension.php file from the Symfony2 framework
     *
     * @return array
     */
    private function retrieveDirs()
    {
        // Discover translation directories
        $dirs = array();
        foreach ($this->container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            if (is_dir($dir = dirname($reflection->getFilename()).'/Resources/translations')) {
                $dirs[] = $dir;
            }
        }

        if (is_dir($dir = $this->container->getParameter('kernel.root_dir').'/Resources/translations')) {
            $dirs[] = $dir;
        }

        return $dirs;
    }
}
