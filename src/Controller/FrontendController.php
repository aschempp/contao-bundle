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
use Symfony\Component\HttpFoundation\Response;

/**
 * Maps the Symfony front end controller to the Contao front end controller
 *
 * @author Leo Feyer <https://contao.org>
 */
class FrontendController extends Controller
{
    /**
     * Converts the output of the Contao front end controller into a Symfony response
     *
     * @return Response The response object
     */
    public function indexAction()
    {
        ob_start();

        $controller = new FrontendIndex();
        $controller->run();

        return new Response(ob_get_clean());
    }
}
