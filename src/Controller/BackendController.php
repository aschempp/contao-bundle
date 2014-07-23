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

use Contao\BackendIndex;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Maps the Symfony back end controller to the Contao back end controller
 *
 * @author Leo Feyer <https://contao.org>
 */
class BackendController extends Controller
{
    public function indexAction()
    {
        $controller = new BackendIndex;
        $controller->run();

        // @todo we should have a Response here, but this is how the FrontendController works too
        exit;
    }
}
