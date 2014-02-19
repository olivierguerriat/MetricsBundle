<?php

namespace Guerriat\MetricsBundle\Metric;


/**
 * Models a set metric
 * @package Guerriat\MetricsBundle\Metric
 */
class SetMetric extends MetricAbstract
{

    /**
     * {@inheritDoc}
     */
    public function __construct($key, $value)
    {
        parent::__construct($key, $value);
        $this->setStatsdMetricCode('s');
    }

}


