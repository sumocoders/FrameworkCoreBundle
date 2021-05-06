<?php

namespace SumoCoders\FrameworkCoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Translation\Translator;

final class ExceptionController extends Controller
{
    public function onExceptionAction(
        FlattenException $exception,
        DebugLoggerInterface $logger = null
    ) {
        /** @var Translator $translator */
        $translator = $this->get('translator');
        $message = $translator->trans('error.messages.generic');

        // check if the error is whitelisted to overrule the message
        if (in_array(
            $exception->getClass(),
            $this->container->getParameter('show_messages_for')
        )) {
            $message = $exception->getMessage();
        }

        // translate page not found messages
        if ('Symfony\Component\HttpKernel\Exception\NotFoundHttpException' == $exception->getClass()) {
            $message = $translator->trans('error.messages.noRouteFound');
        }

        return $this->render(
            '@SumoCodersFrameworkCore/Exception/error.html.twig',
            [
                'status_code' => $exception->getStatusCode(),
                'status_text' => $message,
            ]
        );
    }
}
