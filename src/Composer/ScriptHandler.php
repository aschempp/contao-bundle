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
        $fs   = new Filesystem();
		$root = dirname(dirname(dirname(dirname(dirname(__DIR__)))));

        if (!$fs->exists($root . '/app/config/parameters.yml')) {
            $secret = md5(uniqid(mt_rand(), true));
            $fs->dumpFile($root . '/app/config/parameters.yml', "parameters:\n    locale: en\n    secret: $secret\n");
        }
    }
}
