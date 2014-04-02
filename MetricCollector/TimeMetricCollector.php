<?php

namespace Guerriat\MetricsBundle\MetricCollector;

/**
 * Collects request time
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class TimeMetricCollector extends MetricCollector
{

    /**
     * {@inheritdoc}
     * Set a timer with the request's time
     * @inspiration liuggio/StatsDClientBundle
     */
    public function collect($client, $key, $request, $response, $exception, $master, $ignore_underscore_route)
    {
        if ($master) {
            $route = $request->get('_route');
            if (!$ignore_underscore_route || $route{0} != '_') {
                $startTime = $request->server->get('REQUEST_TIME_FLOAT', $request->server->get('REQUEST_TIME'));

                $time = microtime(true) - $startTime;
                $time = round($time * 1000);
            
                $key = sprintf('%s.%s', $key, $route);
                $client->timer($key, $time);
            }
        }
    }

}


