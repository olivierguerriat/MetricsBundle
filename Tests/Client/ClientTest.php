<?php

namespace Guerriat\MetricsBundle\Tests\Metric;

use Guerriat\MetricsBundle\Client\Client;
use Guerriat\MetricsBundle\Metric\MetricAbstract;
use Guerriat\MetricsBundle\Metric\CounterMetric;
use Guerriat\MetricsBundle\Metric\GaugeMetric;
use Guerriat\MetricsBundle\Metric\SetMetric;
use Guerriat\MetricsBundle\Metric\TimerMetric;

class ClientTest extends \PHPUnit_Framework_TestCase
{

    public function testNoSender()
    {
        $this->setExpectedException('InvalidArgumentException');
        $client = new Client(array());
    }

    public function testAddMetric()
    {
        $metric = new CounterMetric('key');

        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with($this->equalTo($metric));
        $client = new Client(array('fakeSender' => $fakeSender));

        $client->addMetric($metric);
    }

    public function testIncrement()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof CounterMetric
                        && $metric->getValue() == 1
                        && $metric->getSampleRate() == 1
                        && $metric->getKey() == 'app.counter';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('counter', 'Guerriat\MetricsBundle\Metric\CounterMetric');
        $client->increment('app.counter');
    }

    public function testIncrementWithSampleRate()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof CounterMetric
                        && $metric->getValue() == 1
                        && $metric->getSampleRate() == 0.3
                        && $metric->getKey() == 'app.counter';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('counter', 'Guerriat\MetricsBundle\Metric\CounterMetric');
        $client->increment('app.counter', 0.3);
    }

    public function testDecrement()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof CounterMetric
                        && $metric->getValue() == -1
                        && $metric->getKey() == 'app.counter';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('counter', 'Guerriat\MetricsBundle\Metric\CounterMetric');
        $client->decrement('app.counter');
    }

    public function testGauge()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof GaugeMetric
                        && $metric->getValue() == 45
                        && $metric->getKey() == 'app.gauge';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('gauge', 'Guerriat\MetricsBundle\Metric\GaugeMetric');
        $client->gauge('app.gauge', 45);
    }

    public function testSet()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof SetMetric
                        && $metric->getValue() == 42
                        && $metric->getKey() == 'app.set';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('set', 'Guerriat\MetricsBundle\Metric\SetMetric');
        $client->set('app.set', 42);
    }

    public function testTimer()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof TimerMetric
                        && $metric->getValue() == 0.0456
                        && $metric->getKey() == 'app.timer';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('timer', 'Guerriat\MetricsBundle\Metric\TimerMetric');
        $client->timer('app.timer', 0.0456);
    }

    public function testTiming()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof TimerMetric
                        && $metric->getValue() > 0.1
                        && $metric->getKey() == 'app.timer';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('timer', 'Guerriat\MetricsBundle\Metric\TimerMetric');
        $client->timing(
            'app.timer',
            function () {
                usleep(100);
            }
        );
    }

    /**
     * Tests that a given key is always sent to the same server
     * Right now, it is dependent of the 'formula' used in Client
     */
    public function testSenderSelection()
    {

        $fakeSender1 = $this->getMock('Sender', array('addMetric'));
        $fakeSender1->expects($this->once())
            ->method('addMetric');

        $fakeSender2 = $this->getMock('Sender', array('addMetric'));
        $fakeSender2->expects($this->exactly(2))
            ->method('addMetric');

        $client = new Client(array(
            'fakeSender1' => $fakeSender1,
            'fakeSender2' => $fakeSender2
        ));

        $metric1 = new CounterMetric('key'); // to be sent on fakeSender2
        $metric2 = new CounterMetric('key2'); // to be sent on fakeSender1

        $client->addMetric($metric1);
        $client->addMetric($metric1);
        $client->addMetric($metric2);
    }

    /**
     * Sending an Event that wasn't listened to
     */
    public function testHandleEventUnlistenedEvent()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->never())
            ->method('addMetric');

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $this->assertNull($client->handleEvent($fakeEvent));
    }

    /**
     * Sending an Event that hasn't the given method
     */
    public function testHandleEventMissingMethod()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->never())
            ->method('addMetric');

        $client = new Client(array('fakeSender' => $fakeSender));

        $client->setMetricClass('timer', 'Guerriat\MetricsBundle\Metric\TimerMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'timer' => array(
                    'key' => 'app.timer',
                    'method' => 'getTiming',
                )
            )
        );

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $this->setExpectedException('InvalidArgumentException');
        $this->assertNull($client->handleEvent($fakeEvent));
    }

    public function testHandleEventIncrement()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof CounterMetric
                        && $metric->getValue() == 1
                        && $metric->getKey() == 'app.count';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $client->setMetricClass('counter', 'Guerriat\MetricsBundle\Metric\CounterMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'increment' => 'app.count'
            )
        );

        $client->handleEvent($fakeEvent);
    }

    public function testHandleEventDecrement()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof CounterMetric
                        && $metric->getValue() == -1
                        && $metric->getKey() == 'app.count';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $client->setMetricClass('counter', 'Guerriat\MetricsBundle\Metric\CounterMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'decrement' => 'app.count'
            )
        );

        $client->handleEvent($fakeEvent);
    }

    public function testHandleEventTiming()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof TimerMetric
                        && $metric->getValue() == 0.0456
                        && $metric->getKey() == 'app.timer';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName', 'getTime'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $fakeEvent->expects($this->once())
            ->method('getTime')
            ->will($this->returnValue(0.0456));


        $client->setMetricClass('timer', 'Guerriat\MetricsBundle\Metric\TimerMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'timer' => array(
                    'key' => 'app.timer',
                    'method' => 'getTime',
                )
            )
        );

        $client->handleEvent($fakeEvent);
    }


    public function testHandleEventSet()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof SetMetric
                        && $metric->getValue() == 42
                        && $metric->getKey() == 'app.set';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName', 'getVal'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $fakeEvent->expects($this->once())
            ->method('getVal')
            ->will($this->returnValue(42));


        $client->setMetricClass('set', 'Guerriat\MetricsBundle\Metric\SetMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'set' => array(
                    'key' => 'app.set',
                    'method' => 'getVal',
                )
            )
        );

        $client->handleEvent($fakeEvent);
    }

    public function testHandleEventGauge()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->once())
            ->method('addMetric')
            ->with(
                $this->callback(
                    function ($metric) {
                        return $metric instanceof GaugeMetric
                        && $metric->getValue() == 42
                        && $metric->getKey() == 'app.gauge';
                    }
                )
            );

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName', 'getVal'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $fakeEvent->expects($this->once())
            ->method('getVal')
            ->will($this->returnValue(42));


        $client->setMetricClass('gauge', 'Guerriat\MetricsBundle\Metric\GaugeMetric');

        $client->addEventToListen(
            'eventName',
            array(
                'gauge' => array(
                    'key' => 'app.gauge',
                    'method' => 'getVal',
                )
            )
        );

        $client->handleEvent($fakeEvent);
    }

    public function testHandleEventUnknownType()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->never())
            ->method('addMetric');

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $client->addEventToListen(
            'eventName',
            array(
                'foobar' => 'app.foo'
            )
        );

        $this->setExpectedException('InvalidArgumentException');

        $client->handleEvent($fakeEvent);
    }

    public function testHandleEventUnsetType()
    {
        $fakeSender = $this->getMock('Sender', array('addMetric'));
        $fakeSender->expects($this->never())
            ->method('addMetric');

        $client = new Client(array('fakeSender' => $fakeSender));

        $fakeEvent = $this->getMock('Symfony\Component\EventDispatcher\Event', array('getName', 'getVal'));
        $fakeEvent->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('eventName'));

        $fakeEvent->expects($this->never())
            ->method('getVal');

        $client->addEventToListen(
            'eventName',
            array(
                'gauge' => array(
                    'key' => 'app.gauge',
                    'method' => 'getVal',
                )
            )
        );

        $this->setExpectedException('InvalidArgumentException');

        $client->handleEvent($fakeEvent);
    }

}
