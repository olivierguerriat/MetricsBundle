<?php

namespace Guerriat\MetricsBundle\Tests\DependencyInjection;

use Guerriat\MetricsBundle\DependencyInjection\Configuration;

class ConfigurationTest extends \PHPUnit_Framework_TestCase
{

    public function testConfiguration()
    {
        $c = new Configuration();
        $this->assertInstanceOf('\Symfony\Component\Config\Definition\Builder\TreeBuilder', $c->getConfigTreeBuilder());
    }

}
