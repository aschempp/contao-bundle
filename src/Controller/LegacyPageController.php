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

class LegacyPageController
{

    /**
     * @param Request $request
     * @param PageModel $contentDocument
     *
     * @return Response
     */
    public function indexAction(Request $request, PageModel $contentDocument)
    {
        ob_start();

        // TODO: probably need to add some logic from FrontendIndex

        $GLOBALS['objPage'] = $contentDocument;
        $page = new $GLOBALS['TL_PTY'][$contentDocument->type]();

        // Generate the page
        switch ($contentDocument->type)
        {
            case 'root':
            case 'error_404':
                $page->generate($contentDocument->id);
                break;

            case 'error_403':
                $rootPage = PageModel::findByPk($contentDocument->rootId);
                $page->generate($contentDocument->id, $rootPage);
                break;

            default:
                $page->generate($contentDocument, true);
                break;
        }

        $buffer = ob_get_contents();
        ob_end_clean();

        return new Response($buffer);
    }
}