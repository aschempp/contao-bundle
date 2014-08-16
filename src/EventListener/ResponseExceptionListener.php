<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Contao\ContaoBundle
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\ContaoBundle\EventListener;

use Contao\ContaoBundle\Exception\ResponseException;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ResponseExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseException) {
            $event->setResponse($exception->getResponse());
        }
    }
}
