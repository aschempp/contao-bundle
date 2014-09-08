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

use Contao\Config;
use Contao\PageModel;
use Symfony\Cmf\Bundle\RoutingBundle\Model\Route;
use Symfony\Cmf\Component\Routing\Candidates\CandidatesInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Cmf\Component\Routing\RouteProviderInterface;

/**
 * Provider loading routes for front end pages
 *
 * Inspired by Symfony\Cmf\Bundle\RoutingBundle\Doctrine\Orm\RouteProvider
 *
 * @author Andreas Schempp <http://terminal42.ch>
 * @author Leo Feyer <https://contao.org>
 */
class RouteProvider implements RouteProviderInterface
{
    /**
     * @var CandidatesInterface
     */
    private $candidatesStrategy;

    /**
     * @var Config
     */
    private $config;


    public function __construct(CandidatesInterface $candidatesStrategy, Config $config)
    {
        $this->candidatesStrategy = $candidatesStrategy;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteCollectionForRequest(Request $request)
    {
        $table = PageModel::getTable();
        $collection = new RouteCollection();

        $candidates = $this->candidatesStrategy->getCandidates($request);
        if (empty($candidates)) {
            return $collection;
        }

        // Remove slash in alias and removes root alias ("/")
        $candidates = array_filter(
            array_map(
                function($v) {
                    return substr($v, 1);
                },
                $candidates
            )
        );

        // TODO: Maybe we need to check publishing status. Maybe that's the job of another component (e.g. Security)
        $pages = PageModel::findBy(
            array(
                // TODO: get the service from DIC (see contao/contao-bundle#6)
                \Contao\Database::getInstance()->findInSet("$table.alias", $candidates)
                . "OR $table.type='root'"
            ),
            null,
            array(
                'order' => "$table.type='root', $table.alias ASC"
            )
        );

        if (null !== $pages) {
            foreach ($pages as $page) {
                $route = $this->getRouteForPage($page);
                $collection->add($table.'.'.$page->id, $route);
            }
        }

        return $collection;
    }

    /**
     * {@inheritDoc}
     */
    public function getRouteByName($name)
    {
        if (!$this->candidatesStrategy->isCandidate($name)) {
            throw new RouteNotFoundException(sprintf('Route "%s" is not handled by this route provider', $name));
        }

        $page = PageModel::findPublishedByIdOrAlias($name);

        if (!$page) {
            throw new RouteNotFoundException("No route found for name '$name'");
        }

        return $this->getRouteForPage($page);
    }

    /**
     * {@inheritDoc}
     */
    public function getRoutesByNames($names = null)
    {
        // TODO: if we want to be able to dump routes (e.g. on the console), we need to implement this method

        return array();
    }

    /**
     * Find a page and generate a route for it
     *
     * @param PageModel $page
     *
     * @return Route|null
     */
    private function getRouteForPage(PageModel $page)
    {
        $suffix  = substr($this->config->get('urlSuffix'), 1);

        $options = array();
        $options['add_locale_pattern'] = $this->config->get('addLanguageToUrl');
        $options['add_format_pattern'] = ($suffix != '');

        $route = new Route($options);
        $route->setStaticPrefix('/'.($page->type == 'root' ? '' : $page->alias));
        $route->setContent($page);
        $route->setDefault('type', 'page'.ucfirst($page->type));

        $route->setDefault('_format', $suffix);
        $route->setRequirement('_format', $suffix);

        // TODO: do we need to load page details?
        $page->loadDetails();
        $route->setRequirement('_locale', $page->language);

        if ($page->domain != '') {
            $route->setHost($page->domain);
        }

        return $route;
    }
}
