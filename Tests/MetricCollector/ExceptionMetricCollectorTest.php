<?php

namespace Guerriat\MetricsBundle\Tests\MetricCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Guerriat\MetricsBundle\MetricCollector\ExceptionMetricCollector;

class ExceptionMetricCollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCollect()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->once())
            ->method('increment')
            ->with($this->equalTo('key.exception.404'));

        $c = new ExceptionMetricCollector();

        $c->collect($fakeClient, 'key', new Request(), new Response(), new \Exception('', 404), false);
    }

    public function testCollectNullException()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->never())
            ->method('increment');

        $c = new ExceptionMetricCollector();

        $this->assertTrue($c->collect($fakeClient, 'key', new Request(), new Response(), null, false));
    }

}
