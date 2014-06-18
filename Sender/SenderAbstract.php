<?php

namespace Guerriat\MetricsBundle\Sender;

use Symfony\Component\EventDispatcher\Event;
use Guerriat\MetricsBundle\Metric\MetricAbstract;

/**
 * Models a generic sender
 * Send saved metrics on kernel.terminate
 * @package Guerriat\MetricsBundle\Sender
 */
abstract class SenderAbstract
{

    /**
     * @var array contains all metrics to send via this transport
     */
    protected $metrics = array();

    /**
     * Add a metric to be sent on kernel.terminate
     * @param MetricAbstract $metric
     */
    public function addMetric(MetricAbstract $metric)
    {
        $this->metrics[] = $metric;
    }

    /**
     * Send a single metric
     * @param MetricAbstract $metric
     */
    abstract public function sendMetric(MetricAbstract $metric);

    /**
     * Send multiple metrics (default basic implementation)
     * @param array $metrics
     */
    public function sendMetrics(array $metrics)
    {
        foreach ($metrics as $metric) {
            $this->sendMetric($metric);
        }
    }

    /**
     * On kernel.terminate, send the saved metrics
     * @param Event $event
     */
    public function onKernelTerminate(Event $event)
    {
        $this->sendMetrics($this->metrics);
    }

    /**
     * Send saved metrics and clear collection.
     */
    public function flushMetrics()
    {
        $this->sendMetrics($this->metrics);
        $this->metrics = array();
    }
}


