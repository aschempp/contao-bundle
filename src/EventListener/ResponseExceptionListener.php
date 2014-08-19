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

use Contao\ContaoBundle\Exception\ResponseExceptionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

/**
 * Sets the response if the exception is a response exception
 *
 * @author Andreas Schempp <http://terminal42.ch>
 */
class ResponseExceptionListener
{
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof ResponseExceptionInterface) {
            $event->setResponse($exception->getResponse());
        }
    }
}
