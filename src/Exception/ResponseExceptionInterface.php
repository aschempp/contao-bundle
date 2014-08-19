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

namespace Contao\ContaoBundle\Exception;

use Symfony\Component\HttpFoundation\Response;

interface ResponseExceptionInterface
{
    /**
     * Return the response object
     *
     * @return Response
     */
    public function getResponse();
}
