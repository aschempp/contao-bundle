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

namespace Contao\ContaoBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Universal Contao front end router
 *
 * @author Andreas Schempp <http://terminal42.ch>
 * @author Leo Feyer <https://contao.org>
 */
class ContaoLoader extends Loader
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function load($resource, $type = null)
    {
        $config = $this->container->get('contao.config');

        $addlang = $config->get('addLanguageToUrl');
        $suffix  = substr($config->get('urlSuffix'), 1);

        $routes = new RouteCollection();

        $defaults = [
            '_controller' => 'ContaoBundle:Frontend:index'
        ];

        $pattern = '/{alias}';
        $require = ['alias' => '.*'];

        // URL suffix
        if ($suffix != '') {
            $pattern .= '.{_format}';

            $require['_format']  = $suffix;
            $defaults['_format'] = $suffix;
        }

        // Add language to URL
        if ($addlang) {
            $require['_locale'] = '[a-z]{2}(\-[A-Z]{2})?';

            $route = new Route('/{_locale}' . $pattern, $defaults, $require);
            $routes->add('contao_locale', $route);
        }

        // Default route
        $route = new Route($pattern, $defaults, $require);
        $routes->add('contao_default', $route);

        // Empty domain (root)
        $route = new Route('/', $defaults);
        $routes->add('contao_root', $route);

        return $routes;
    }

    public function supports($resource, $type = null)
    {
        return 'contao_frontend' === $type;
    }
}
