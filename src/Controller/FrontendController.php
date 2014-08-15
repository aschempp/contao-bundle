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

namespace Contao\ContaoBundle\Controller;

use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\FrontendIndex;
use Contao\Input;
use Contao\Session;
use Contao\System;
use Symfony\Bundle\FrameworkBundle\Controller\Controller as SymfonyController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps the Symfony front end controller to the Contao front end controller
 *
 * @author Leo Feyer <https://contao.org>
 */
class FrontendController extends SymfonyController
{
    public function indexAction()
    {
        $buffer = $this->outputFromCache();

        if ($buffer === false) {
            ob_start();

            $controller = new FrontendIndex;
            $controller->run();

            $buffer = ob_get_contents();
            ob_end_clean();
        }

        return new Response($buffer);
    }

    protected function outputFromCache()
    {
        // Build the page if a user is (potentially) logged in or there is POST data
        if (!empty($_POST) || Input::cookie('FE_USER_AUTH') || Input::cookie('FE_AUTO_LOGIN') || $_SESSION['DISABLE_CACHE'] || isset($_SESSION['LOGIN_ERROR']) || Config::get('debugMode')) {
            return false;
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
                return false;
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
            return false;
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
            return false;
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

        // Send the status header (see #6585)
        if ($type == 'error_403') {
            header('HTTP/1.1 403 Forbidden');
        } elseif ($type == 'error_404') {
            header('HTTP/1.1 404 Not Found');
        } else {
            header('HTTP/1.1 200 Ok');
        }

        header('Vary: User-Agent', false);
        header('Content-Type: ' . $content . '; charset=' . Config::get('characterSet'));

        // Send the cache headers
        if ($expire !== null && (Config::get('cacheMode') == 'both' || Config::get('cacheMode') == 'browser')) {
            header('Cache-Control: public, max-age=' . ($expire - time()));
            header('Expires: ' . gmdate('D, d M Y H:i:s', $expire) . ' GMT');
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT');
            header('Pragma: public');
        } else {
            header('Cache-Control: no-cache');
            header('Cache-Control: pre-check=0, post-check=0', false);
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
            header('Expires: Fri, 06 Jun 1975 15:10:00 GMT');
            header('Pragma: no-cache');
        }

        return $buffer;
    }
}
