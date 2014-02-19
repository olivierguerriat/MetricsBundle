<?php

namespace Guerriat\MetricsBundle\Metric;

/**
 * Models a counter metric to be incremented
 * @package Guerriat\MetricsBundle\Metric
 */
class CounterMetric extends MetricAbstract
{

    protected $sampleRate = 1;

    /**
     * {@inheritDoc}
     */
    public function __construct($key, $value = 1)
    {
        parent::__construct($key, $value);
        $this->setStatsdMetricCode('c');
    }

    /**
     * Set the sample rate, allowing the sender to send only a portion of messages.
     * @param float $value between 0 (excluded) & 1
     * @throws \InvalidArgumentException
     */
    public function setSampleRate($value)
    {
        if ($value > 1 || $value <= 0) {
            throw new \InvalidArgumentException('Invalid sample rate for metric "' . $this->key . '".');
        }
        $this->sampleRate = $value;
    }

    /**
     * Get the sample rate
     * @return float
     */
    public function getSampleRate()
    {
        return $this->sampleRate;
    }

}


