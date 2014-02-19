<?php

namespace Guerriat\MetricsBundle\Tests\Monolog;

use Guerriat\MetricsBundle\Monolog\MetricFormatter;

class MockMetricFormatter extends MetricFormatter
{

    public $formatCalledCounter = 0;

    public function format(array $record)
    {
        $this->formatCalledCounter++;

        return array('formatted');
    }

}

class MetricFormatterTest extends \PHPUnit_Framework_TestCase
{

    public function testGetFirstWords()
    {
        $formatter = new MetricFormatter(null, true, true, 2);
        $this->assertEquals('Lorem-ipsum', $formatter->getFirstWords('Lorem ipsum dolor sit amet.'));
        $this->assertEquals('Lorem-ipsum', $formatter->getFirstWords('Lorem [ipsum] dolor sit amet.'));
        $this->assertEquals('Lorem-ipsum', $formatter->getFirstWords('Lorem "ipsum" dolor sit amet.'));
        $this->assertEquals('Lorem-lipsum', $formatter->getFirstWords('Lorem l\'ipsum dolor sit amet.'));
        $this->assertEquals('Lorem-ipseum', $formatter->getFirstWords('Lorem_ipséum dolor sit amet.'));

        $formatter = new MetricFormatter(null, true, true, 3);
        $this->assertEquals('Lorem-ipsum-dolor', $formatter->getFirstWords('Lorem ipsum dolor sit amet.'));
        $this->assertEquals('Lorem-ipsum-dolor', $formatter->getFirstWords('Lorem [ipsum] dolor sit amet.'));
        $this->assertEquals('Lorem-ipsum-dolor', $formatter->getFirstWords('Lorem "ipsum" dolor sit amet.'));
        $this->assertEquals('Lorem-lipsum-dolor', $formatter->getFirstWords('Lorem l\'ipsum dolor sit amet.'));
        $this->assertEquals('Lorem-ipseum-dolor', $formatter->getFirstWords('Lorem_ipséum dolor sit amet.'));
        $this->assertEquals('Lorem-ipseum-dolor', $formatter->getFirstWords('Lorem_ipséum---dolor sit amet.'));
    }

    public function testFormat()
    {
        $f = new MetricFormatter("%level_name%.%short_message%");
        $formatted = $f->format(
            array(
                'level_name' => 'WARNING',
                'message' => 'lala',
            )
        );
        $this->assertEquals(array('WARNING.lala'), $formatted);
        $formatted = $f->format(
            array(
                'level_name' => 'WARNING',
                'message' => 'What a nice log message',
            )
        );
        $this->assertEquals(array('WARNING.What-a'), $formatted);
    }

    public function testFormatMoreWords()
    {
        $f = new MetricFormatter("%level_name%.%short_message%", false, false, 4);
        $formatted = $f->format(
            array(
                'level_name' => 'WARNING',
                'message' => 'What a nice log message',
            )
        );
        $this->assertEquals(array('WARNING.What-a-nice-log'), $formatted);
    }

    public function testFormatExtraContext()
    {
        $f = new MetricFormatter("%level_name%.%short_message%", true, true, 2);
        $formatted = $f->format(
            array(
                'level_name' => 'WARNING',
                'message' => 'What a nice log message',
                'context' => array(
                    'version' => 3,
                    'dev' => true,
                ),
                'extra' => array(
                    'user' => 42,
                ),
            )
        );
        $this->assertEquals(
            array(
                'WARNING.What-a',
                'WARNING.What-a.context.version.3',
                'WARNING.What-a.context.dev.true',
                'WARNING.What-a.extra.user.42'
            ),
            $formatted
        );
    }

    public function testFormatBatch()
    {
        $f = new MockMetricFormatter();
        $ret = $f->formatBatch(
            array(
                array(),
                array(),
                array()
            )
        );

        $this->assertEquals(array('formatted', 'formatted', 'formatted'), $ret);
        $this->assertEquals(3, $f->formatCalledCounter);
    }

}
