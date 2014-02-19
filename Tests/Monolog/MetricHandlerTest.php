<?php

namespace Guerriat\MetricsBundle\Tests\Metric;

use Monolog\Handler\AbstractProcessingHandler;

use Guerriat\MetricsBundle\Monolog\MetricHandler;


class MetricHandlerTest extends \PHPUnit_Framework_TestCase
{

    public function testHandlerSolo()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->once())
            ->method('increment')
            ->with($this->equalTo('key.log-message'));

        $h = new MetricHandler($fakeClient, 'key', 6);

        $h->write(
            array(
                'formatted' => 'log-message'
            )
        );

    }

    public function testHandlerMultiple()
    {
        $fakeClient = $this->getMock('Client', array('increment'));
        $fakeClient->expects($this->exactly(2))
            ->method('increment')
            ->with($this->equalTo('key.log-message'));

        $h = new MetricHandler($fakeClient, 'key', 6);

        $h->write(
            array(
                'formatted' => array(
                    'log-message',
                    'log-message'
                ),
            )
        );

    }

}
