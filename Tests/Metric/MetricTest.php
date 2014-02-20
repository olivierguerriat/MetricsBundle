<?php

namespace Guerriat\MetricsBundle\Tests\Metric;

use Guerriat\MetricsBundle\Metric\CounterMetric;
use Guerriat\MetricsBundle\Metric\GaugeMetric;
use Guerriat\MetricsBundle\Metric\SetMetric;
use Guerriat\MetricsBundle\Metric\TimerMetric;

class MetricTest extends \PHPUnit_Framework_TestCase
{

    public function testCounterMetric()
    {
        $metric = new CounterMetric('app.counter', 1);
        $this->assertEquals('app.counter', $metric->getKey());
        $this->assertEquals(1, $metric->getValue());
        $this->assertEquals(1, $metric->getSampleRate());
        $this->assertEquals('c', $metric->getStatsdMetricCode());

        $metric->setSampleRate(0.5);
        $this->assertEquals(0.5, $metric->getSampleRate());

        $metric->setValue(4);
        $this->assertEquals(4, $metric->getValue());
    }

    public function testIncrementMetricWrongSampleRate()
    {
        $metric = new CounterMetric('app.counter');
        $this->setExpectedException('InvalidArgumentException');
        $metric->setSampleRate(2);
    }

    public function testGaugeMetric()
    {
        $metric = new GaugeMetric('app.gauge', 39);
        $this->assertEquals('app.gauge', $metric->getKey());
        $this->assertEquals(39, $metric->getValue());
        $this->assertEquals('g', $metric->getStatsdMetricCode());
    }

    public function testSetMetric()
    {
        $metric = new SetMetric('app.set', 10);
        $this->assertEquals('app.set', $metric->getKey());
        $this->assertEquals(10, $metric->getValue());
        $this->assertEquals('s', $metric->getStatsdMetricCode());
        $metric->setValue('bla');
        $this->assertEquals('bla', $metric->getValue());
    }

    public function testTimerMetric()
    {
        $metric = new TimerMetric('app.timer', 0.09896);
        $this->assertEquals('app.timer', $metric->getKey());
        $this->assertEquals(0.09896, $metric->getValue());
        $this->assertEquals('ms', $metric->getStatsdMetricCode());
    }

    public function testSetKeyEmpty()
    {
        $metric = new CounterMetric('app.counter');
        $this->setExpectedException('InvalidArgumentException');
        $metric->setKey('');
    }

    public function testSetValueEmpty()
    {
        $metric = new CounterMetric('app.counter');
        $this->setExpectedException('InvalidArgumentException');
        $metric->setValue('');
    }

    public function testInvalidValue()
    {
        $metric = new CounterMetric('app.counter');
        $this->setExpectedException('InvalidArgumentException');
        $metric->setValue('foo');
    }

    public function testInvalidValueSet()
    {
        $this->setExpectedException('InvalidArgumentException');
        $metric = new SetMetric('app.set', '');
    }

}
