<?php

namespace Guerriat\MetricsBundle\Tests\MetricCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Guerriat\MetricsBundle\MetricCollector\HitMetricCollector;

class HitMetricCollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCollect()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->once())
            ->method('increment')
            ->with($this->equalTo('key._bar'));

        $fakeRequest = $this->getMock('Request', array('get'));
        $fakeRequest->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_route'))
            ->will($this->returnValue('_bar'));

        $c = new HitMetricCollector();

        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, true);

        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, false);
    }

}
