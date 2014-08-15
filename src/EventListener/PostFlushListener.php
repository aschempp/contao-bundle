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

namespace Contao\ContaoBundle\EventListener;

use Contao\System;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

/**
 * Triggers the "postFlushData" hook
 *
 * @author Leo Feyer <https://contao.org>
 */
class PostFlushListener
{
    public function onKernelTerminate(PostResponseEvent $event)
    {
        if (isset($GLOBALS['TL_HOOKS']['postFlushData']) && is_array($GLOBALS['TL_HOOKS']['postFlushData'])) {
            foreach ($GLOBALS['TL_HOOKS']['postFlushData'] as $callback) {
                System::importStatic($callback[0])->$callback[1]($event);
            }
        }
    }
}
