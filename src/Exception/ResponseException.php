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

class ResponseException extends \RuntimeException implements ResponseExceptionInterface
{
    private $content;
    private $statusCode;
    private $headers;


    /**
     * Constructor
     *
     * @param string     $content    The content string
     * @param int        $statusCode The HTTP status code
     * @param array      $headers    An array of HTTP headers
     * @param string     $message    The exception message
     * @param int        $code       The exception code
     * @param \Exception $previous   The previous exception
     */
    public function __construct($content = '', $statusCode = 200, array $headers = array(), $message = null, $code = 0, \Exception $previous = null)
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Return the response object
     *
     * @return Response
     */
    public function getResponse()
    {
        $response = new Response($this->getContent(), $this->statusCode, $this->headers);

        // Prevent the Symfony exception handler from overwriting the response status code
        $response->headers->set('X-Status-Code', $this->statusCode);

        return $response;
    }

    /**
     * Get the response content
     *
     * @return string The response content
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Get the HTTP status code
     *
     * @return int The HTTP status code
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Get the array of HTTP headers
     *
     * @return array The array of HTTP headers
     */
    public function getHeaders()
    {
        return $this->headers;
    }
}