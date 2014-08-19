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
     * @param string     $template   The template name
     * @param int        $statusCode The HTTP status code
     * @param array      $headers    An array of HTTP headers
     * @param string     $message    The exception message
     * @param int        $code       The exception code
     * @param \Exception $previous   The previous exception
     */
    public function __construct($template, $statusCode = 200, array $headers = [], $message = null, $code = 0, \Exception $previous = null)
    {
        $this->template = basename($template);

        parent::__construct('', $statusCode, $message, $headers, $code, $previous);
    }

    /**
     * Return the template name
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
        $file = $this->getTemplateFile();

        if ($file === false) {
            return $this->getMessage();
        }

        ob_start();
        include TL_ROOT . '/' . $file;

        return ob_get_clean();
    }

    /**
     * Search for a custom template and return the path
     *
     * @return string|bool The custom template path or false if there is no custom template
     */
    protected function getTemplateFile()
    {
        if ($this->template == '') {
            return false;
        }

        if (file_exists(TL_ROOT . '/templates/' . $this->template . '.html5')) {
            return 'templates/' . $this->template . '.html5';
        }

        if (file_exists(TL_ROOT . '/system/modules/core/templates/backend/' . $this->template . '.html5')) {
            return 'system/modules/core/templates/backend/' . $this->template . '.html5';
        }

        return false;
    }
}
