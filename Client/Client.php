<?php

namespace Guerriat\MetricsBundle\Client;

use Symfony\Component\EventDispatcher\Event;

use Guerriat\MetricsBundle\Metric\MetricAbstract;
use Guerriat\MetricsBundle\Metric\KeyFormatter;

/**
 * Symfony service providing helpers to send metrics to one or more Senders
 * @package Guerriat\MetricsBundle\Client
 */
class Client
{

    protected $senders = array();
    protected $nbSenders = 0;
    protected $senderAliases = array();
    
    protected $prefix = false;

    protected $metricClasses = array();

    protected $listenedEvents = array();

    /**
     * Initialize the Client and set the Senders list
     * @param array $senders
     * @param string $prefix
     * @throws \InvalidArgumentException
     */
    public function __construct($senders, $prefix = false)
    {
        $this->senders = $senders;
        $this->nbSenders = count($senders);
        $this->senderAliases = array_keys($senders);
        if ($this->nbSenders == 0) {
            throw new \InvalidArgumentException('A Client should have at least one Sender.');
        }
        
        if ($prefix) {
            $this->prefix = KeyFormatter::format($prefix, false, false, '-', '.');
        }
    }

    /**
     * Set the class to use for a metric type
     * @param string $type
     * @param string $class
     */
    public function setMetricClass($type, $class)
    {
        $this->metricClasses[$type] = $class;
    }

    /**
     * Increment a counter
     * The sampleRate is used to send only a portion of the events
     * (it is fully managed by the Sender and we can change the value without compromising data consistency)
     * @param string $key
     * @param float $sampleRate between 0 (excluded) & 1
     */
    public function increment($key, $sampleRate = 1.0)
    {
        $this->counter($key, 1, $sampleRate);
    }

    /**
     * Decrement a counter
     * The sampleRate is used to send only a portion of the events
     * (it is fully managed by the Sender and we can change the value without compromising data consistency)
     * @param string $key
     * @param float $sampleRate between 0 (excluded) & 1
     */
    public function decrement($key, $sampleRate = 1.0)
    {
        $this->counter($key, -1, $sampleRate);
    }

    /**
     * Add to a counter
     * The sampleRate is used to send only a portion of the events
     * (it is fully managed by the Sender and we can change the value without compromising data consistency)
     * @param string $key
     * @param int $value
     * @param float $sampleRate between 0 (excluded) & 1
     */
    public function counter($key, $value, $sampleRate = 1.0)
    {
        $metric = new $this->metricClasses['counter']($key, $value);
        $metric->setSampleRate($sampleRate);
        $this->addMetric($metric);
    }

    /**
     * Set a gauge's value
     * @param string $key
     * @param string $value
     */
    public function gauge($key, $value)
    {
        $metric = new $this->metricClasses['gauge']($key, $value);
        $this->addMetric($metric);
    }

    /**
     * Add an item to a set
     * @param string $key
     * @param string $value
     */
    public function set($key, $value)
    {
        $metric = new $this->metricClasses['set']($key, $value);
        $this->addMetric($metric);
    }

    /**
     * Set a timer's value
     * @param string $key
     * @param string $value
     */
    public function timer($key, $value)
    {
        $metric = new $this->metricClasses['timer']($key, $value);
        $this->addMetric($metric);
    }

    /**
     * Measure the time the given function takes to execute and set a timer
     * @param string $key
     * @param callable $callable
     */
    public function timing($key, $callable)
    {
        $start = microtime(true);
        call_user_func($callable);
        $this->timer($key, (microtime(true) - $start) * 1000);
    }

    /**
     * Add a metric
     * (and prefix the key if a prefix is set)
     * @param MetricAbstract $metric
     */
    public function addMetric(MetricAbstract $metric)
    {
        if ($this->prefix) {
            $key = $metric->getKey();
            $key = $this->prefix.'.'.$key;
            $metric->setKey($key);
        }
        $server = $this->getSender($metric->getKey());
        $server->addMetric($metric);
    }

    /**
     * Return a Sender instance based on the key
     * This is useful when multiple servers are configured. A given key will always go to the same server.
     * @param string $key
     * @return Sender
     * @inspiration M6Web/StatsdBundle
     */
    protected function getSender($key)
    {
        $alias = $this->senderAliases[(int)(crc32($key) % $this->nbSenders)];
        return $this->senders[$alias];
    }

    /**
     * Add a event to listen
     * Metrics detailed in the config will be sent when the event is fired
     * @param string $name
     * @param array $config
     */
    public function addEventToListen($name, $config)
    {
        $this->listenedEvents[$name] = $config;
    }

    /**
     * Handle a listened event
     * @param Event $event
     * @param string $name
     * @throws \InvalidArgumentException
     */
    public function handleEvent(Event $event, $name = null)
    {
        $name = $name ? : $event->getName(); // For Symfony < 2.4
        if (!isset($this->listenedEvents[$name])) {
            return;
        }
        $config = $this->listenedEvents[$name];
        foreach ($config as $type => $value) {
            switch ($type) {
                case 'increment':
                    $this->increment($value);
                    break;
                case 'decrement':
                    $this->decrement($value);
                    break;
                case 'counter':
                case 'set':
                case 'gauge':
                case 'timer':
                    $this->eventMetric($type, $value['key'], $event, $value['method']);
                    break;
                default:
                    throw new \InvalidArgumentException('Unknown metric type ' . $type . '.');
            }
        }
    }

    /**
     * Call the given method on the event and set the metric
     * @param string $type
     * @param string $key
     * @param Event $event
     * @param string $method
     * @throws \InvalidArgumentException
     * @inspiration M6Web/StatsdBundle
     */
    protected function eventMetric($type, $key, Event $event, $method)
    {
        if (!isset($this->metricClasses[$type])) {
            throw new \InvalidArgumentException('Unknown metric type ' . $type . '.');
        }
        if (!method_exists($event, $method)) {
            throw new \InvalidArgumentException('The event class ' . get_class($event) .
                ' must have a ' . $method . ' method in order to set the ' . $type . '.');
        }
        $value = call_user_func(array($event, $method));
        if ($value != null) {
            $metric = new $this->metricClasses[$type]($key, $value);
            $this->addMetric($metric);
        }
    }

}


