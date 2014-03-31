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
    public function collect($client, $key, $request, $response, $exception, $master)
    {
        if ($master) {
            $key = sprintf('%s.%s.%s', $key, $response->getStatusCode(), $request->get('_route'));
            $client->increment($key);
        }
    }

}


