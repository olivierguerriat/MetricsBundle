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
            ->with($this->equalTo('key._bar'), $this->greaterThan(0));

        $fakeRequest = $this->getMock('Request', array('get'));
        $fakeRequest->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_route'))
            ->will($this->returnValue('_bar'));
        
        $fakeRequest->server = $this->getMock('Server', array('get'));
        
        $fakeRequest->server->expects($this->exactly(2))
            ->method('get')
            ->with($this->anything())
            ->will($this->returnValue(2));
        
        $c = new TimeMetricCollector();

        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, true, false);

        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, false, false);
    }
    
    public function testCollectWithIgnoreUnderscoreRoute()
    {
        $fakeClient = $this->getMock('Client', array('timer'));
        $fakeClient->expects($this->once())
            ->method('timer')
            ->with($this->equalTo('key.foo'), $this->greaterThan(0));

        $fakeRequest = $this->getMock('Request', array('get'));
        $fakeRequest->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_route'))
            ->will($this->returnValue('_bar'));
        
        $fakeRequest->server = $this->getMock('Server', array('get'));
        
        $fakeRequest->server->expects($this->never())
            ->method('get');
        
        $c = new TimeMetricCollector();

        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, true, true);
        
        $fakeRequest = $this->getMock('Request', array('get'));
        $fakeRequest->expects($this->once())
            ->method('get')
            ->with($this->equalTo('_route'))
            ->will($this->returnValue('foo'));
        $fakeRequest->server = $this->getMock('Server', array('get'));
        $fakeRequest->server->expects($this->exactly(2))
            ->method('get')
            ->with($this->anything())
            ->will($this->returnValue(2));
        
        $c->collect($fakeClient, 'key', $fakeRequest, new Response(), null, true, true);

    }

}
