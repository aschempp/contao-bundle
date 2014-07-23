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

namespace Contao\ContaoBundle\Event;

use Contao\ContaoBundle\Exception\IncompleteInstallationException;
use Contao\ContaoBundle\Exception\InsecureDocumentRootException;
use Contao\ContaoBundle\Exception\InvalidRequestTokenException;
use Contao\ContaoBundle\Exception\TemplatedMessageException;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ContaoSubscriber extends ContainerAware implements EventSubscriberInterface
{
    /**
     * Construct the event subscriber
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns events this subscriber will handle
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'kernel.request'    => array('onKernelRequest', -50),
            'kernel.exception'  => array('onKernelException', -50),
        );
    }

    /**
     * Check if Contao Core is successfully bootet or throw appropriate messages if not
     *
     * @param GetResponseEvent $event
     *
     * @throws \Contao\ContaoBundle\Exception\IncompleteInstallationException
     * @throws \Contao\ContaoBundle\Exception\InsecureDocumentRootException
     * @throws \Contao\ContaoBundle\Exception\InvalidRequestTokenException
     *
     * @todo we should likely not check for PHP_SAPI or TL_SCRIPT here, as these routes should handle the exception correctly
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Show the "insecure document root" message
        if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && substr(\Environment::get('path'), -4) == '/web' && !\Config::get('ignoreInsecureRoot')) {
            throw new InsecureDocumentRootException('be_insecure', 'Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
        }

        // Show the "incomplete installation" message
        if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && !$GLOBALS['objConfig']->isComplete()) {
            throw new IncompleteInstallationException('be_incomplete', 'The installation has not been completed. Open the Contao install tool to continue.');
        }

        // Check the request token upon POST requests
        if ($_POST && !\RequestToken::validate(\Input::post('REQUEST_TOKEN'))) {
            throw new InvalidRequestTokenException('be_referer', 'Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.');
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof InvalidRequestTokenException) {

            // Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
            if (\Environment::get('isAjaxRequest')) {

                $response = new Response(
                    '',
                    Response::HTTP_NO_CONTENT,
                    array(
                        'X-Ajax-Location' => \Environment::get('base') . 'contao/'
                    )
                );

            } else {

                $response = new Response(
                    $exception->getTemplatedMessage(),
                    Response::HTTP_BAD_REQUEST
                );
            }

            $event->setResponse($response);

        } elseif ($exception instanceof TemplatedMessageException) {

            $event->setResponse(
                new Response($exception->getTemplatedMessage())
            );

        }
    }
}