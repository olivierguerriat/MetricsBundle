<?php

namespace Guerriat\MetricsBundle\MetricCollector;

use Guerriat\MetricsBundle\Metric\KeyFormatter;

/**
 * Collects raised exceptions
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class ExceptionMetricCollector extends MetricCollector
{

    /**
     * {@inheritdoc}
     * Increment a counter for each exception's code
     * @inspiration liuggio/StatsDClientBundle
     */
    public function collect($client, $key, $request, $response, $exception, $master, $ignore_underscore_route)
    {
        if (null === $exception) {
            return true;
        }
        $key = sprintf('%s.exception.%s', $key, KeyFormatter::format($exception->getCode()));
        $client->increment($key);
    }

}


