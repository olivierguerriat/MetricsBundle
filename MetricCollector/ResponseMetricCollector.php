<?php

namespace Guerriat\MetricsBundle\MetricCollector;

/**
 * Collects responses
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class ResponseMetricCollector extends MetricCollector
{

    /**
     * {@inheritdoc}
     * Increment a counter for the master response
     */
    public function collect($client, $key, $request, $response, $exception, $master, $ignore_underscore_route)
    {
        if ($master) {
            $route = $request->get('_route');
            if (!$ignore_underscore_route || $route{0} != '_') {
                $key = sprintf('%s.%s.%s', $key, $response->getStatusCode(), $route);
                $client->increment($key);
            }
        }
    }

}


