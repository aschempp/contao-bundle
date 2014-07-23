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

class TemplatedMessageException extends \Exception
{
    /**
     * Template name
     * @var string
     */
    protected $template;

    /**
     * Initialize new exception with a template
     * @param string     $template
     * @param string     $message
     * @param int        $code
     * @param \Exception $previous
     */
    public function __construct($template = '', $message = '', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);

        $this->setTemplate($template);
    }

    /**
     * Set the template name
     * @param $template
     */
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * Get the template name
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Find and return the template file
     * @return string|false
     */
    public function getTemplateFile()
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

    /**
     * Get message based on template or fallback
     * @return string
     */
    public function getTemplatedMessage()
    {
        $strFile = $this->getTemplateFile();

        if (false === $strFile) {
            return $this->getMessage();
        }

        ob_start();
        include $strFile;

        return ob_get_clean();
    }
} 