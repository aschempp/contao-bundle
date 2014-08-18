<?php
/**
 * Created by PhpStorm.
 * User: aschempp
 * Date: 16.08.14
 * Time: 08:04
 */

namespace Contao\ContaoBundle\EventListener;


use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\FrontendIndex;
use Contao\Input;
use Contao\Session;
use Contao\System;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;


/**
 * Output page from cache without loading controllers
 */
class OutputFromCacheListener
{
    private $config;

    /**
     * Constructor
     *
     * @param Config $config The Contao config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Output cache for current URL if available before the router handles anything
     *
     * @param GetResponseEvent $event The kernel.request event
     *
     * @throws \Exception
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Build the page if a user is (potentially) logged in or there is POST data
        if (!empty($_POST) || Input::cookie('FE_USER_AUTH') || Input::cookie('FE_AUTO_LOGIN') || $_SESSION['DISABLE_CACHE'] || isset($_SESSION['LOGIN_ERROR']) || Config::get('debugMode')) {
            return;
        }

        /**
         * If the request string is empty, look for a cached page matching the
         * primary browser language. This is a compromise between not caching
         * empty requests at all and considering all browser languages, which
         * is not possible for various reasons.
         */
        if (Environment::get('request') == '' || Environment::get('request') == 'index.php') {

            // Return if the language is added to the URL and the empty domain will be redirected
            if (Config::get('addLanguageToUrl') && !Config::get('doNotRedirectEmpty')) {
                return;
            }

            $language = Environment::get('httpAcceptLanguage');
            $cacheKey = Environment::get('base') .'empty.'. $language[0];
        } else {
            $cacheKey = Environment::get('base') . Environment::get('request');
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['getCacheKey']) && is_array($GLOBALS['TL_HOOKS']['getCacheKey'])) {
            foreach ($GLOBALS['TL_HOOKS']['getCacheKey'] as $callback) {
                $cacheKey = System::importStatic($callback[0])->$callback[1]($cacheKey);
            }
        }

        $found = false;
        $cacheFile = null;

        // Check for a mobile layout
        if (Input::cookie('TL_VIEW') == 'mobile' || (Environment::get('agent')->mobile && Input::cookie('TL_VIEW') != 'desktop')) {
            $cacheKey = md5($cacheKey . '.mobile');
            $cacheFile = TL_ROOT . '/system/cache/html/' . substr($cacheKey, 0, 1) . '/' . $cacheKey . '.html';

            if (file_exists($cacheFile)) {
                $found = true;
            }
        }

        // Check for a regular layout
        if (!$found) {
            $cacheKey = md5($cacheKey);
            $cacheFile = TL_ROOT . '/system/cache/html/' . substr($cacheKey, 0, 1) . '/' . $cacheKey . '.html';

            if (file_exists($cacheFile)) {
                $found = true;
            }
        }

        // Return if the file does not exist
        if (!$found) {
            return;
        }

        $expire = null;
        $content = null;
        $type = null;

        // Include the file
        ob_start();
        require_once $cacheFile;

        // The file has expired
        if ($expire < time()) {
            ob_end_clean();
            return;
        }

        // Read the buffer
        $buffer = ob_get_contents();
        ob_end_clean();

        // Session required to determine the referer
        $session = Session::getInstance();
        $data = $session->getData();

        // Set the new referer
        if (!isset($_GET['pdf']) && !isset($_GET['file']) && !isset($_GET['id']) && $data['referer']['current'] != Environment::get('requestUri')) {
            $data['referer']['last'] = $data['referer']['current'];
            $data['referer']['current'] = substr(Environment::get('requestUri'), strlen(Environment::get('path')) + 1);
        }

        // Store the session data
        $session->setData($data);

        // Load the default language file (see #2644)
        System::loadLanguageFile('default');

        // Replace the insert tags and then re-replace the request_token
        // tag in case a form element has been loaded via insert tag
        $buffer = Controller::replaceInsertTags($buffer, false);
        $buffer = str_replace(['{{request_token}}', '[{]', '[}]'], [REQUEST_TOKEN, '{{', '}}'], $buffer);

        // Content type
        if (!$content) {
            $content = 'text/html';
        }

        $response = new Response($buffer);

        // Send the status header (see #6585)
        if ($type == 'error_403') {
            $response->setStatusCode(Response::HTTP_FORBIDDEN);
        } elseif ($type == 'error_404') {
            $response->setStatusCode(Response::HTTP_NOT_FOUND);
        }

        $response->headers->set('Vary', 'User-Agent', false);
        $response->headers->set('Content-Type', $content . '; charset=' . Config::get('characterSet'));

        // Send the cache headers
        if ($expire !== null && (Config::get('cacheMode') == 'both' || Config::get('cacheMode') == 'browser')) {
            $response->headers->set('Cache-Control', 'public, max-age=' . ($expire - time()));
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', $expire) . ' GMT');
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s', time()) . ' GMT');
            $response->headers->set('Pragma', 'public');
        } else {
            $response->headers->set('Cache-Control', array('no-cache', 'pre-check=0, post-check=0'));
            $response->headers->set('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
            $response->headers->set('Expires', 'Fri, 06 Jun 1975 15:10:00 GMT');
            $response->headers->set('Pragma', 'no-cache');
        }

        $event->setResponse($response);
    }
} 