<?php

namespace Guerriat\MetricsBundle\Tests\Metric;

use Guerriat\MetricsBundle\Metric\KeyFormatter;

class KeyFormatterTest extends \PHPUnit_Framework_TestCase
{

    public function testFormat()
    {
        $this->assertEquals('Lorem-ipsum', KeyFormatter::format('Lorem ipsum dolor sit amet.', 2));
        $this->assertEquals('Lorem-ipsum', KeyFormatter::format('Lorem [ipsum] dolor sit amet.', 2));
        $this->assertEquals('Lorem-ipsum', KeyFormatter::format('Lorem "ipsum" dolor sit amet.', 2));
        $this->assertEquals('Lorem-lipsum', KeyFormatter::format('Lorem l\'ipsum dolor sit amet.', 2));
        $this->assertEquals('Lorem-ipseum', KeyFormatter::format('Lorem_ipséum dolor sit amet.', 2));
        $this->assertEquals('Lorem-ipsum-dolor', KeyFormatter::format('Lorem ipsum dolor sit amet.', 3));
        $this->assertEquals('Lorem-i', KeyFormatter::format('Lorem ipsum dolor sit amet.', 3, 7));
        $this->assertEquals('Lorem', KeyFormatter::format('Lorem ipsum dolor sit amet.', false, 6));
        $this->assertEquals('Lorem--ipsum--dolor', KeyFormatter::format('Lorem ipsum dolor sit amet.', 3, 500, '--'));
        $this->assertEquals('456', KeyFormatter::format(456));
        $this->assertEquals('456', KeyFormatter::format(-456));
        $this->assertEquals('456', KeyFormatter::format(-456, 1));
        $this->assertEquals('', KeyFormatter::format(false));
        $this->assertEquals('1', KeyFormatter::format(true));
        $this->assertEquals('Lecla', KeyFormatter::format("L'éclair au chocolat", false, 5));
        $this->assertEquals('Leclair.au.chocolat', KeyFormatter::format("L'éclair au chocolat", false, false, '.'));
        $this->assertEquals('Leclair.au.choc', KeyFormatter::format("L'éclair au chocolat", 3, 15, '.'));
        $this->assertEquals('eclair-au-chocolat', KeyFormatter::format('eclair.au.chocolat', false, false, '-'));
        $this->assertEquals('eclair.au.chocolat', KeyFormatter::format('eclair.au.chocolat', false, false, '-', '.'));
        $this->assertEquals('eclair.au.chocolat', KeyFormatter::format('eclair.@u.chocolat', false, false, '-', '.'));
        $this->assertEquals('ec-air.au.chocolat', KeyFormatter::format('ec£air.au.chocolat', false, false, '-', '.'));
        $this->assertEquals('ec£air.au.chocolat', KeyFormatter::format('ec£air.au.chocolat', false, false, '-', '.£'));
        $this->assertEquals('eclair-chocolat', KeyFormatter::format('eclair.§.chocolat'));
        $this->assertEquals('eclair-§-chocolat', KeyFormatter::format('eclair.§.chocolat', false, false, '-', '§'));
    }

    public function testFormatWrongGlue()
    {
        $this->setExpectedException('InvalidArgumentException');
        KeyFormatter::format('Lorem ipsum dolor sit amet.', 3, 500, '+');
    }

    public function testFormatArray()
    {
        $this->setExpectedException('InvalidArgumentException');
        KeyFormatter::format(array('bla', 'bla'));
        
        $this->setExpectedException('InvalidArgumentException');
        KeyFormatter::format(null);
    }

}
