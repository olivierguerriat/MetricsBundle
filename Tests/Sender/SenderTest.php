<?php

namespace Guerriat\MetricsBundle\Tests\Sender;

use Guerriat\MetricsBundle\Sender\SenderAbstract;
use Guerriat\MetricsBundle\Metric\MetricAbstract;
use Guerriat\MetricsBundle\Metric\CounterMetric;

use Symfony\Component\EventDispatcher\Event;

class MockSender extends SenderAbstract
{

    public $nbMetricsSent = 0;
    public $sendMetricsCalled = false;

    public function sendMetric(MetricAbstract $metric)
    {
        $this->nbMetricsSent++;
    }

    public function sendMetrics(array $metrics)
    {
        $this->sendMetricsCalled = true;
        parent::sendMetrics($metrics);
    }

}

class SenderTest extends \PHPUnit_Framework_TestCase
{

    public function testSender()
    {
        $sender = new MockSender();

        $metric = new CounterMetric('key');
        $sender->addMetric($metric);
        $sender->addMetric($metric);

        $sender->onKernelTerminate(new Event);

        $this->assertTrue($sender->sendMetricsCalled);
        $this->assertEquals(2, $sender->nbMetricsSent);
    }

}
