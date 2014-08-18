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
    public function __construct($template, $statusCode = 200, array $headers = array(), $message = null, $code = 0, \Exception $previous = null)
    {
        $this->template = $template;

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
     * Return the template content
     *
     * @return string
     */
    public function getContent()
    {
        $strFile = $this->getTemplateFile();

        if ($strFile === false) {
            return $this->getMessage();
        }

        ob_start();
        include $strFile;

        return ob_get_clean();
    }

    /**
     * Return the path to a custom template
     *
     * @return string|bool The custom template path or false if there is no custom template
     */
    protected function getTemplateFile()
    {
        if ($this->template == '') {
            return false;
        } else if (file_exists(TL_ROOT . "/templates/$this->template.html5")) {
            return TL_ROOT . "/templates/$this->template.html5";
        } elseif (file_exists(TL_ROOT . "/system/modules/core/templates/backend/$this->template.html5")) {
            return TL_ROOT . "/system/modules/core/templates/backend/$this->template.html5";
        } else {
            return false;
        }
    }
}