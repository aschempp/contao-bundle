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

use Contao\FrontendIndex;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Maps the Symfony front end controller to the Contao front end controller
 *
 * @author Leo Feyer <https://contao.org>
 */
class FrontendController extends Controller
{
    public function indexAction()
    {
        $controller = new FrontendIndex;
        $controller->run();
    }
}
