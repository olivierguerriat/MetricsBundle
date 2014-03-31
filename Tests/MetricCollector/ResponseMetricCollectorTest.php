<?php

namespace Guerriat\MetricsBundle\Tests\MetricCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Guerriat\MetricsBundle\MetricCollector\ResponseMetricCollector;

class ResponseMetricCollectorTest extends \PHPUnit_Framework_TestCase
{

    public function testCollect()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->once())
            ->method('increment')
            ->with($this->equalTo('key.200._bar'));

        $fakeRequest = $this->getMock('Request', array('get'));
        $fakeRequest->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_route'))
            ->will($this->returnValue('_bar'));
        
        $fakeResponse = $this->getMock('Response', array('getStatusCode'));
        $fakeResponse->expects($this->once())
            ->method('getStatusCode')
            ->will($this->returnValue(200));

        $c = new ResponseMetricCollector();

        $c->collect($fakeClient, 'key', $fakeRequest, $fakeResponse, null, true);

        $c->collect($fakeClient, 'key', $fakeRequest, $fakeResponse, null, false);
    }

}
