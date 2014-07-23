<?php
/**
 * Created by PhpStorm.
 * User: aschempp
 * Date: 22.07.14
 * Time: 23:21
 */

namespace Contao\ContaoBundle\Event;


use Contao\ContaoBundle\Exception\IncompleteInstallationException;
use Contao\ContaoBundle\Exception\InsecureDocumentRootException;
use Contao\ContaoBundle\Exception\InvalidRequestTokenException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ContaoSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return array(
            'kernel.request'    => array('onKernelRequest', -50),
            'kernel.exception'  => array('onKernelException', -50),
        );
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        // Show the "insecure document root" message
        if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && substr(\Environment::get('path'), -4) == '/web' && !\Config::get('ignoreInsecureRoot')) {
            throw new InsecureDocumentRootException('Your installation is not secure. Please set the document root to the <code>/web</code> subfolder.');
        }

        // Show the "incomplete installation" message
        if (PHP_SAPI != 'cli' && TL_SCRIPT != 'contao/install.php' && !$GLOBALS['objConfig']->isComplete()) {
            throw new IncompleteInstallationException('The installation has not been completed. Open the Contao install tool to continue.');
        }

        // Check the request token upon POST requests
        if ($_POST && !\RequestToken::validate(\Input::post('REQUEST_TOKEN'))) {

            // Force a JavaScript redirect upon Ajax requests (IE requires absolute link)
            if (\Environment::get('isAjaxRequest')) {
                // @todo this is wrong
                header('HTTP/1.1 204 No Content');
                header('X-Ajax-Location: ' . Environment::get('base') . 'contao/');
                exit;

            } else {
                throw new InvalidRequestTokenException('Invalid request token. Please <a href="javascript:window.location.href=window.location.href">go back</a> and try again.');
            }
        }
    }

    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $exception = $event->getException();

        if ($exception instanceof IncompleteInstallationException) {

            $event->setResponse(
                new Response($this->getMessage('be_incomplete', $exception->getMessage()))
            );

        } elseif ($exception instanceof InsecureDocumentRootException) {

            $event->setResponse(
                new Response($this->getMessage('be_insecure', $exception->getMessage()))
            );

        } elseif ($exception instanceof InvalidRequestTokenException) {

            $event->setResponse(
                new Response(
                    $this->getMessage('be_referer', $exception->getMessage()),
                    Response::HTTP_BAD_REQUEST
                )
            );
        }
    }


    /**
     * Try to load a message from template instead of just a text
     * @param string
     * @param string
     * @return string
     */
    private function getMessage($strTemplate, $strFallback)
    {
        ob_start();

        if (file_exists(TL_ROOT . "/templates/$strTemplate.html5"))
        {
            include TL_ROOT . "/templates/$strTemplate.html5";
        }
        elseif (file_exists(TL_ROOT . "/system/modules/core/templates/backend/$strTemplate.html5"))
        {
            include TL_ROOT . "/system/modules/core/templates/backend/$strTemplate.html5";
        }
        else
        {
            echo $strFallback;
        }

        return ob_get_clean();
    }
}