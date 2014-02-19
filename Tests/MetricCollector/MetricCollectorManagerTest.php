<?php

namespace Guerriat\MetricsBundle\Tests\MetricCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;


use Guerriat\MetricsBundle\MetricCollector\MetricCollectorManager;
use Guerriat\MetricsBundle\Client\Client;
use Guerriat\MetricsBundle\Sender\StatsdSender;

class MetricCollectorManagerTest extends \PHPUnit_Framework_TestCase
{

    protected function getClient() {
        return new Client(array(new StatsdSender(array(
            'host' => '',
            'port' => '',
            'udp_max_size' => 512,
        ))));
    }

    public function testCollect()
    {
        $client = $this->getClient();
        $key = 'key';
        $req = new Request();
        $res = new Response();

        $manager = new MetricCollectorManager($client);

        $fakeCollector = $this->getMock('MetricCollector', array('collect'));
        $fakeCollector->expects($this->once())
            ->method('collect')
            ->with(
                $this->equalTo($client),
                $this->equalTo($key),
                $this->equalTo($req),
                $this->equalTo($res),
                null,
                $this->isFalse()
            );

        $manager->addCollector($key, $fakeCollector);

        $manager->onKernelResponse(
            new FilterResponseEvent($this->getMock(
                '\Symfony\Component\HttpKernel\HttpKernelInterface'
            ), $req, 'bla', $res)
        );
    }

    public function testCollectException()
    {
        $client = $this->getClient();
        $key = 'key';
        $req = new Request();
        $res = new Response();

        $exception = new \Exception();
        $exceptionEvent = $this->getMockBuilder('\Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent')
            ->disableOriginalConstructor()
            ->getMock();
        $exceptionEvent->expects($this->once())
            ->method('getException')
            ->will($this->returnValue($exception));

        $manager = new MetricCollectorManager($client);

        $fakeCollector = $this->getMock('MetricCollector', array('collect'));
        $fakeCollector->expects($this->once())
            ->method('collect')
            ->with(
                $this->equalTo($client),
                $this->equalTo($key),
                $this->equalTo($req),
                $this->equalTo($res),
                $this->equalTo($exception),
                $this->isFalse()
            );

        $manager->addCollector($key, $fakeCollector);

        $manager->onKernelException($exceptionEvent);

        $manager->onKernelResponse(
            new FilterResponseEvent($this->getMock(
                '\Symfony\Component\HttpKernel\HttpKernelInterface'
            ), $req, 'http', $res)
        );
    }

    public function testSubscribedEvent()
    {
        $this->assertThat(MetricCollectorManager::getSubscribedEvents(), $this->isType('array'));
    }

}
