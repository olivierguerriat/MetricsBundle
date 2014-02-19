<?php

namespace Guerriat\MetricsBundle\Metric;

/**
 * Models a generic metric
 * @package Guerriat\MetricsBundle\Metric
 */
abstract class MetricAbstract
{

    protected $key;
    protected $value;
    protected $statsdMetricCode;

    /**
     * Create a Metric with a key and a value
     * @param string $key
     * @param float $value
     */
    public function __construct($key, $value)
    {
        $this->setKey($key);
        $this->setValue($value);
    }

    /**
     * Set the key
     * @param string $key
     * @throws \InvalidArgumentException
     */
    public function setKey($key)
    {
        if (empty($key)) {
            throw new \InvalidArgumentException('Metric key can\'t be empty.');
        }
        $this->key = $key;
    }

    /**
     * Get the key
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * Set the value
     * @param float $value
     * @throws \InvalidArgumentException
     */
    public function setValue($value)
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Metric value can\'t be empty.');
        }
        else if (!is_numeric($value)) {
            throw new \InvalidArgumentException('Metric value must be numeric.');
        }
        $this->value = $value;
    }

    /**
     * Get the value
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the statsd metric code (one or two letters)
     * @param string $code
     */
    public function setStatsdMetricCode($code)
    {
        $this->statsdMetricCode = $code;
    }

    /**
     * Get the statsd metric code
     * @return string
     */
    public function getStatsdMetricCode()
    {
        return $this->statsdMetricCode;
    }

}


