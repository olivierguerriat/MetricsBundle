<?php

namespace Guerriat\MetricsBundle\Tests\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Guerriat\MetricsBundle\DependencyInjection\GuerriatMetricsExtension;

class GuerriatMetricsExtensionTest extends \PHPUnit_Framework_TestCase
{

    public function testExtension()
    {
        $container = new ContainerBuilder();

        $ext = new GuerriatMetricsExtension();
        $ext->load(
            array(
                array(
                    'servers' => array(
                        'srv1' => array(),
                        'srv2' => array(),
                    ),
                    'clients' => array(
                        'default' => array(
                            'monolog' => array(
                                'enable' => true,
                                'formatter' => array(),
                            ),
                            'events' => array(
                                'event_name' => array(),
                            )
                        ),
                        'bla' => array(
                            'servers' => array('srv2'),
                            'collectors' => array(
                                'service_name' => 'key'
                            ),
                            'monolog' => array(
                                'enable' => true,
                                'formatter' => array(),
                            )
                        ),
                    )
                )
            ),
            $container
        );

        $d = $container->getDefinition('guerriat_metrics.sender.srv1');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.sender.srv2');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.bla');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.bla.collector.manager');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.monolog.formatter');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.monolog.handler');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.bla.monolog.formatter');
        $this->assertNotNull($d);

        $d = $container->getDefinition('guerriat_metrics.bla.monolog.handler');
        $this->assertNotNull($d);

    }

    public function testExtensionWrongServer()
    {
        $container = new ContainerBuilder();

        $this->setExpectedException('\Exception');

        $ext = new GuerriatMetricsExtension();
        $ext->load(
            array(
                array(
                    'servers' => array(
                        'default' => array(),
                    ),
                    'clients' => array(
                        'default' => array(
                            'servers' => array('srv2'),
                        ),
                    )
                )
            ),
            $container
        );

    }

}
