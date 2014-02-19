<?php

namespace Guerriat\MetricsBundle\Metric;


/**
 * Models a timer metric
 * @package Guerriat\MetricsBundle\Metric
 */
class TimerMetric extends MetricAbstract
{

    /**
     * {@inheritDoc}
     */
    public function __construct($key, $value)
    {
        parent::__construct($key, $value);
        $this->setStatsdMetricCode('ms');
    }

}


