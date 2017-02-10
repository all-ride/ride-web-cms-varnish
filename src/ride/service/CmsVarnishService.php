<?php

namespace ride\service;

use ride\library\cms\node\Node;
use ride\library\cms\node\SiteNode;
use ride\library\varnish\VarnishServer;

use ride\web\cms\Cms;

/**
 * Service for the Varnish integration with the CMS
 */
class CmsVarnishService {

    /**
     * Array with the Varnish server instances to work with
     * @var array
     * @see \ride\library\varnish\VarnishServer
     */
    protected $varnishServers = array();

    /**
     * Constructs the CMS varnish service
     * @param \ride\web\Cms $cms
     * @return null
     */
    public function __construct(Cms $cms) {
        $this->cms = $cms;
    }

    /**
     * Gets the locales
     * @return array
     */
    public function getLocales() {
        return $this->cms->getLocales();
    }

    /**
     * Adds a Varnish server instance to the service
     * @param \ride\library\varnish\VarnishServer $varnishServer
     * @return null
     */
    public function addVarnishServer(VarnishServer $varnishServer) {
        $this->varnishServers[] = $varnishServer;
    }

    /**
     * Bans the provided URL
     * @param string $banUrl URL to be banned
     * @param boolean $recursive Set to true to ban everything starting with
     * the provided URL
     * @return null
     */
    public function banUrl($banUrl, $recursive = false) {
        foreach ($this->varnishServers as $varnishServer) {
            $varnishServer->banUrl($banUrl, $recursive);
        }
    }

    /**
     * Bans the provided URL's
     * @param array $banUrls URL's to be banned
     * @param boolean $recursive Set to true to ban everything starting with
     * the provided URL's
     * @return null
     */
    public function banUrls(array $banUrls, $recursive = false) {
        foreach ($this->varnishServers as $varnishServer) {
            $varnishServer->banUrls($banUrls, $recursive);
        }
    }

    /**
     * Bans a node
     * @param \ride\library\cms\node\Node $node Node to ban
     * @param string $baseUrl Base URL to the system
     * @param string $locale Code of the locale
     * @param boolean $recursive Flag to see if child nodes should be banned
     * @return null
     */
    public function banNode(Node $node, $baseUrl, $locale = null, $recursive = false) {
        $banUrls = $this->getBanUrls($node, $baseUrl, $locale, $recursive);
        foreach ($banUrls as $url => $recursive)  {
            $this->banUrl($url, $recursive);
        }
    }

    /**
     * Gets all the URLs which need to be banned
     * @param \ride\library\cms\node\Node $node Node to ban
     * @param string $baseUrl Base URL to the system
     * @param string $locale Code of the locale
     * @param boolean $recursive Flag to see if child nodes should be banned
     * @param array $result
     * @return array
     */
    protected function getBanUrls(Node $node, $baseUrl, $locale = null, $recursive = false, array $result = array()) {
        if ($node instanceof SiteNode) {
            $recursive = true;
        }

        if ($locale === null) {
            $urls = $node->getUrls($baseUrl);

            $baseUrls = $urls;
            foreach ($baseUrls as $locale => $nodeUrl) {
                $urls = $this->getWidgetUrls($urls, $node, $locale, $nodeUrl);
            }
        } else {
            $nodeUrl = $node->getUrl($locale, $baseUrl);

            $urls = array($nodeUrl);
            $urls = $this->getWidgetUrls($urls, $node, $locale, $nodeUrl);
        }

        foreach ($urls as $url) {
            $result[$url] = $recursive;
            if (!$recursive) {
                $result[$url . '?'] = true;
            }
        }

        if (!$recursive || $node instanceof SiteNode) {
            return $result;
        }

        $children = $node->getChildren();
        if ($children) {
            foreach ($children as $child) {
                $result = $this->getBanUrls($child, $baseUrl, $locale, true, $result);
            }
        }

        return $result;
    }

    /**
     * Gets all the extra widget URL's
     * @param array $result Result to add URL's to
     * @param \ride\library\cms\node\Node $node Node to process
     * @param string $locale
     * @param string $nodeUrl
     * @return array
     */
    protected function getWidgetUrls(array $result, Node $node, $locale, $nodeUrl) {
        $theme = $this->cms->getTheme($node->getTheme());
        $regions = $theme->getRegions();

        foreach ($regions as $region => $null) {
            $sections = $node->getSections($region);
            foreach ($sections as $section => $layout) {
                $widgets = $node->getWidgets($region, $section);
                foreach ($widgets as $block => $widgets) {
                    foreach ($widgets as $widgetId => $widget) {
                        $widget = $this->cms->getWidget($widget);
                        if (!$widget) {
                            continue;
                        }

                        $widget = clone $widget;
                        $widget->setIdentifier($widgetId);
                        $widget->setRegion($region);
                        $widget->setSection($section);
                        $widget->setBlock($block);
                        $widget->setProperties($node->getWidgetProperties($widgetId));
                        $widget->setLocale($locale);

                        $routes = $widget->getRoutes();
                        if (!$routes) {
                            continue;
                        }

                        foreach ($routes as $route) {
                            $path = $route->getPath();

                            $pathTokens = $route->getPathTokens();
                            foreach ($pathTokens as $token) {
                                if (substr($token, 0, 1) == '%' && substr($token, -1) == '%') {
                                    $path = str_replace($token, '*', $path);
                                }
                            }

                            $result[] = $nodeUrl . $path;
                        }
                    }
                }
            }
        }

        return $result;
    }

}
