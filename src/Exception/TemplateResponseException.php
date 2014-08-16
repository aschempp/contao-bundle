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
    /**
     * Template name
     * @var string
     */
    private $template;

    /**
     * @param string     $template
     * @param int        $statusCode
     * @param string     $message
     * @param array      $headers
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($template, $statusCode = 200, $message = null, array $headers = array(), $code = 0, \Exception $previous = null)
    {
        $this->template = $template;

        parent::__construct('', $statusCode, $message, $headers, $code, $previous);
    }

    /**
     * Get the template name
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Get response content from template and fall back to message
     *
     * @return string
     */
    public function getContent()
    {
        $strFile = $this->getTemplateFile();

        if (false === $strFile) {
            return $this->getMessage();
        }

        ob_start();
        include $strFile;

        return ob_get_clean();
    }

    /**
     * Find and return the template file
     * @return string|false
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