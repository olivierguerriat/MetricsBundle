<?php

namespace Guerriat\MetricsBundle\Tests\MetricCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Guerriat\MetricsBundle\MetricCollector\TimeMetricCollector;

class TimeMetricCollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCollect()
    {
        $fakeClient = $this->getMock('Client', array('timer'));
        $fakeClient->expects($this->once())
            ->method('timer')
            ->with($this->equalTo('key'), $this->greaterThan(0));

        $c = new TimeMetricCollector();

        $c->collect($fakeClient, 'key', new Request(), new Response(), null, true);

        $c->collect($fakeClient, 'key', new Request(), new Response(), null, false);
    }

}
