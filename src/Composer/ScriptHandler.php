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

namespace Contao\ContaoBundle\Composer;

use Symfony\Component\Filesystem\Filesystem;

/**
 * Provides methods to be executed by Composer
 *
 * @author Leo Feyer <https://contao.org>
 */
class ScriptHandler
{
    public static function setupParameters()
    {
        $fs  = new Filesystem();
		$dir = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        if (!$fs->exists($dir . '/app/config/parameters.yml')) {
            $secret = md5(uniqid(mt_rand(), true));
            $fs->dumpFile($dir . '/app/config/parameters.yml', "parameters:\n    locale: en\n    secret: $secret\n");
        }
    }
}
