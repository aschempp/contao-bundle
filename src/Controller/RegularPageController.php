<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2014 Leo Feyer
 *
 * @package Core
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

namespace Contao\ContaoBundle\Controller;


use Contao\FrontendIndex;
use Contao\PageModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RegularPageController
{

    /**
     * @param Request $request
     * @param PageModel $contentDocument
     *
     * @return Response
     */
    public function indexAction(Request $request, PageModel $contentDocument)
    {
        // Someone must have replaced the regular page
        // Use legacy controller to render the custom TL_PTY
        if (isset($GLOBALS['TL_PTY']['regular'])) {
            $controller = new LegacyPageController();
            return $controller->indexAction($request, $contentDocument);
        }

        // TODO: remove $GLOBALS['TL_PTY']['regular'] and implement the frontend controller here
    }
}