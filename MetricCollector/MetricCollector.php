<?php

namespace Guerriat\MetricsBundle\MetricCollector;

/**
 * Models a metric collector
 * @package Guerriat\MetricsBundle\MetricCollector
 */
abstract class MetricCollector
{

    /**
     * Is called on kernel.response
     * @param Client $client the associated client
     * @param string $key the associated key
     * @param Request $request
     * @param Response $response
     * @param Exception $exception
     * @param boolean $master whether it is the master request
     * @param boolean $ignore_underscore_route whether it should ignore route starting with '_'
     * @inspiration liuggio/StatsDClientBundle
     */
    abstract public function collect($client, $key, $request, $response, $exception, $master, $ignore_underscore_route);

}


