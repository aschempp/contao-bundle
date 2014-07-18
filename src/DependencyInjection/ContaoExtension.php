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

namespace Contao\ContaoBundle\DependencyInjection;

use Contao\Config;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * Registers the bundle services
 *
 * @author Leo Feyer <https://contao.org>
 */
class ContaoExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config')
        );

        $loader->load('services.yml');

        if (!defined('TL_ROOT')) {
            define('TL_ROOT', dirname(dirname(dirname(dirname(dirname(__DIR__))))));
        }

        // Preload the Contao configuration if not done yet
        if (!isset($GLOBALS['TL_CONFIG'])) {
            Config::preload();
        }

        // Complete the database settings
        $container->setParameter('database_driver', 'pdo_mysql');
        $container->setParameter('database_host', $GLOBALS['TL_CONFIG']['dbHost']);
        $container->setParameter('database_port', $GLOBALS['TL_CONFIG']['dbPort']);
        $container->setParameter('database_name', $GLOBALS['TL_CONFIG']['dbDatabase']);
        $container->setParameter('database_user', $GLOBALS['TL_CONFIG']['dbUser']);
        $container->setParameter('database_password', $GLOBALS['TL_CONFIG']['dbPass']);

        // Complete the mailer settings
        if (!$GLOBALS['TL_CONFIG']['useSMTP']) {
            $container->setParameter('mailer_transport', 'mail');
        } else {
            $container->setParameter('mailer_transport', 'smtp');
            $container->setParameter('mailer_host', $GLOBALS['TL_CONFIG']['smtpHost']);
            $container->setParameter('mailer_user', $GLOBALS['TL_CONFIG']['smtpUser']);
            $container->setParameter('mailer_password', $GLOBALS['TL_CONFIG']['smtpPass']);
        }

        // Use the encryption key as kernel secret
        $container->setParameter('kernel.secret', $GLOBALS['TL_CONFIG']['encryptionKey']);

        // Set the default charset
        $container->setParameter('kernel.charset', $GLOBALS['TL_CONFIG']['characterSet']);
    }
}
