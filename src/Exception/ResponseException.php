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
use Symfony\Component\HttpKernel\Exception\HttpException;

class ResponseException extends HttpException
{
    /**
     * Response content
     * @var string
     */
    private $content;

    /**
     * @param string     $content
     * @param int        $statusCode
     * @param array      $headers
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($content = '', $statusCode = 200, $message = null, array $headers = array(), $code = 0, \Exception $previous = null)
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }

    /**
     * Get response content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get response object
     *
     * @return Response
     */
    public function getResponse()
    {
        $response = new Response($this->getContent(), $this->getStatusCode(), $this->getHeaders());

        // Set the explicit status code to prevent Symfony exception handler from overwriting the Response status code
        $response->headers->set('X-Status-Code', $this->getStatusCode());

        return $response;
    }
}