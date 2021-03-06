<?php

namespace Guerriat\MetricsBundle\MetricCollector;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Manages metric collectors
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class MetricCollectorManager implements EventSubscriberInterface
{

    protected $client;
    protected $collectors = array();

    protected $exception = null;
    
    protected $ignore_underscore_route;

    /**
     * Setup the collector listener and set its assigned Client
     * @param Client $client
     * @param boolean $ignore_underscore_route
     */
    function __construct($client, $ignore_underscore_route = true)
    {
        $this->client = $client;
        $this->ignore_underscore_route = $ignore_underscore_route;
    }

    /**
     * Add a collector
     * @param string $key
     * @param MetricCollector $collector
     */
    public function addCollector($key, $collector)
    {
        $this->collectors[$key] = $collector;
    }

    /**
     * Save the exception
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $this->exception = $event->getException();
    }

    /**
     * Call the collector on kernel.response
     * @param FilterResponseEvent $event
     * @inspiration liuggio/StatsDClientBundle
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $master = HttpKernelInterface::MASTER_REQUEST === $event->getRequestType();
        foreach ($this->collectors as $key => $collector) {
            $collector->collect($this->client, $key, $event->getRequest(), $event->getResponse(), $this->exception, $master, $this->ignore_underscore_route);
        }
        $this->exception = null;
    }

    /**
     * Announce listened events and corresponding methods
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            KernelEvents::RESPONSE => array('onKernelResponse', -100),
            KernelEvents::EXCEPTION => 'onKernelException',
        );
    }

}


