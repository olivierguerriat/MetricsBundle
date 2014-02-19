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
    public function collect($client, $key, $request, $response, $exception, $master)
    {
        if ($master) {
            $key = sprintf('%s.%s', $key, $request->get('_route'));
            $client->increment($key);
        }
    }

}


