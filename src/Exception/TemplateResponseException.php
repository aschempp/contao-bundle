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

use Contao\TemplateLoader;

/**
 * Creates a response object from a template
 *
 * @author Andreas Schempp <http://terminal42.ch>
 */
class TemplateResponseException extends ResponseException
{
    private $template;

    /**
     * Constructor
     *
     * @param string     $template   The template path
     * @param int        $statusCode The HTTP status code
     * @param array      $headers    An array of HTTP headers
     * @param string     $message    The exception message
     * @param int        $code       The exception code
     * @param \Exception $previous   The previous exception
     */
    public function __construct($template, $statusCode = 200, array $headers = [], $message = null, $code = 0, \Exception $previous = null)
    {
        $this->template = $template;

        parent::__construct('', $statusCode, $message, $headers, $code, $previous);
    }

    /**
     * Return the template path
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get the response content
     *
     * @return string
     */
    public function getContent()
    {
        $file = $this->getTemplatePath();

        if ($file === false) {
            return $this->getMessage();
        }

        ob_start();
        include $file;

        return ob_get_clean();
    }

    /**
     * Resolve the template path
     *
     * @return string|bool The template path or false if the template does not exist
     */
    protected function getTemplatePath()
    {
        if ($this->template == '') {
            return false;
        }

        if (strpos($this->template, '../') !== false) {
            return false;
        }

        $name = basename($this->template);

        if (file_exists(TL_ROOT . '/templates/' . $name . '.html5')) {
            return TL_ROOT . '/templates/' . $name . '.html5';
        }

        if (file_exists(TL_ROOT . '/' . $this->template . '.html5')) {
            return TL_ROOT . '/' . $this->template . '.html5';
        }

        return false;
    }
}
