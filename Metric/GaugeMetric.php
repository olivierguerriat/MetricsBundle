<?php

namespace Guerriat\MetricsBundle\Metric;


/**
 * Models a gauge metric
 * @package Guerriat\MetricsBundle\Metric
 */
class GaugeMetric extends MetricAbstract
{

    /**
     * {@inheritDoc}
     */
    public function __construct($key, $value)
    {
        parent::__construct($key, $value);
        $this->setStatsdMetricCode('g');
    }

}


