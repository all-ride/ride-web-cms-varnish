<?php

namespace ride\web\cms;

use ride\library\event\Event;
use ride\library\event\EventManager;

use ride\service\CmsVarnishService;

use ride\web\WebApplication;

/**
 * Application listener to handle Varnish for the CMS
 */
class VarnishApplicationListener {

    /**
     * Instance of the varnish server
     * @var \ride\service\CmsVarnishService
     */
    private $varnish;

    /**
     * Nodes to ban
     * @var array
     */
    private $banNodes;

    /**
     * Flag to see if a clear action is required
     * @var boolean
     */
    private $needsAction;

    /**
     * Constructs a new Varnish application listener
     * @param \ride\library\varnish\VarnishServer $varnish
     * @return null
     */
    public function __construct(CmsVarnishService $varnishService) {
        $this->varnishService = $varnishService;
        $this->banNodes = array();
    }

    /**
     * Handles a node save or remove action
     * @param \ride\library\event\Event $event Save or remove event
     * @param \ride\library\event\EventManager $eventManager Instance of the
     * event manager
     * @return null
     */
    public function handleCmsAction(Event $event, EventManager $eventManager) {
        if ($event->getArgument('action') != 'publish') {
            return;
        }

        // retrieve the saved nodes and mark the nodes for ban
        $nodes = $event->getArgument('nodes');
        foreach ($nodes as $node) {
            $this->banNodes[$node->getId()] = $node;
        }

        // retrieve the deleted nodes and mark the nodes for ban
        $deletedNodes = $event->getArgument('deletedNodes');
        if ($deletedNodes) {
            foreach ($deletedNodes as $node) {
                $this->banNodes[$node->getId()] = $node;
            }
        }

        // register event to clear when the controller has finished processing
        // the request
        if (!$this->needsAction && $this->banNodes) {
            $eventManager->addEventListener('app.response.pre', array($this, 'handleVarnish'), 2);

            $this->needsAction = true;
        }
    }

    /**
     * Performs a clear on all the received nodes
     * @param \ride\library\event\Event $event Pre response event
     * @param \ride\web\WebApplication $web Instance of the web application
     * @return null
     */
    public function handleVarnish(Event $event) {
        $web = $event->getArgument('web');
        $request = $web->getRequest();
        $baseUrl = $request->getBaseUrl();

        $locales = $this->varnishService->getLocales();

        foreach ($this->banNodes as $node) {
            foreach ($locales as $locale => $null) {
                $this->varnishService->banNode($node, $baseUrl, $locale);
            }
        }

        $this->banNodes = array();
        $this->needsAction = false;
    }

}
