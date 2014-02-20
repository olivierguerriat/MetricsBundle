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

    /**
     * {@inheritDoc}
     */
    public function setValue($value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Metric value can\'t be empty.');
        }
        $this->value = $value;
    }
}


