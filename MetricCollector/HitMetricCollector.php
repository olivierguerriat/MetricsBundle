<?php

namespace Guerriat\MetricsBundle\MetricCollector;

/**
 * Collects hits
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class HitMetricCollector extends MetricCollector
{

    /**
     * {@inheritdoc}
     * Increment a counter for the master request
     */
    public function collect($client, $key, $request, $response, $exception, $master, $ignore_underscore_route)
    {
        if ($master) {
            $route = $request->get('_route');
            if (!$ignore_underscore_route || $route{0} != '_') {
                $key = sprintf('%s.%s', $key, $route);
                $client->increment($key);
            }
        }
    }

}


