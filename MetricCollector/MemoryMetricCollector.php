<?php

namespace Guerriat\MetricsBundle\MetricCollector;

/**
 * Collects peak memory usage
 * @package Guerriat\MetricsBundle\MetricCollector
 */
class MemoryMetricCollector extends MetricCollector
{
    
    /**
     * Calculate the peak used by php in KB.
     * @return int
     * @inspiration liuggio/StatsDClientBundle
     */
    static private function getMemoryUsage()
    {
        $bit = memory_get_peak_usage(true);
        if ($bit > 1024) {
            return intval($bit / 1024);
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     * Set a gauge to peak memory usage
     */
    public function collect($client, $key, $request, $response, $exception, $master)
    {
        $client->gauge($key, self::getMemoryUsage());
    }

}


