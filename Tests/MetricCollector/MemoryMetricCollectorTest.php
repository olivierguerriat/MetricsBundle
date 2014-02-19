<?php

namespace Guerriat\MetricsBundle\MetricCollector {

    use Guerriat\MetricsBundle\Tests\MetricCollector\MemoryMetricCollectorTest;

    function memory_get_peak_usage($bool)
    {
        return MemoryMetricCollectorTest::$memoryPeak;
    }

}

namespace Guerriat\MetricsBundle\Tests\MetricCollector {

    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

    use Guerriat\MetricsBundle\MetricCollector\MemoryMetricCollector;

    class MemoryMetricCollectorTest extends \PHPUnit_Framework_TestCase
    {

        public static $memoryPeak;

        public function testCollect()
        {
            $fakeClient = $this->getMock('Client', array('gauge'));
            $fakeClient->expects($this->once())
                ->method('gauge')
                ->with($this->equalTo('key'), $this->equalTo(2));

            $c = new MemoryMetricCollector();

            self::$memoryPeak = 2048;

            $c->collect($fakeClient, 'key', new Request(), new Response(), null, false);
        }

        public function testCollectZero()
        {
            $fakeClient = $this->getMock('Client', array('gauge'));
            $fakeClient->expects($this->once())
                ->method('gauge')
                ->with($this->equalTo('key'), $this->equalTo(0));

            $c = new MemoryMetricCollector();

            self::$memoryPeak = 100;

            $c->collect($fakeClient, 'key', new Request(), new Response(), null, false);
        }

    }

}