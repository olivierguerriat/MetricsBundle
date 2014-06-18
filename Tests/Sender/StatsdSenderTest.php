<?php


namespace Guerriat\MetricsBundle\Sender {

    use Guerriat\MetricsBundle\Tests\Sender\StatsdSenderTest;

    function fsockopen($address, $port, &$errno, &$errstr)
    {
        StatsdSenderTest::$fsockopenCalled = true;
        StatsdSenderTest::$fsockopenLastAddress = $address;
        StatsdSenderTest::$fsockopenLastPort = $port;

        return StatsdSenderTest::$fsockopenReturn;
    }

    function fwrite($fp, $string)
    {
        StatsdSenderTest::$fwriteCalled = true;
        StatsdSenderTest::$fwriteLastString = $string;
        if (StatsdSenderTest::$fwriteException) {
            throw new \Exception();
        }

        return true;
    }

    function fclose($fp)
    {
        StatsdSenderTest::$fcloseCalled = true;

        return true;
    }

}

namespace Guerriat\MetricsBundle\Tests\Sender {

    use Guerriat\MetricsBundle\Sender\StatsdSender;
    use Guerriat\MetricsBundle\Metric\MetricAbstract;
    use Guerriat\MetricsBundle\Metric\CounterMetric;
    use Guerriat\MetricsBundle\Metric\DecrementMetric;
    use Guerriat\MetricsBundle\Metric\GaugeMetric;
    use Guerriat\MetricsBundle\Metric\SetMetric;
    use Guerriat\MetricsBundle\Metric\TimerMetric;

    use Symfony\Component\EventDispatcher\Event;

    class MockStatsdSender extends StatsdSender
    {

        public $lastSentMessage;
        public $processMetricCalledCounter = 0;
        public $sendMessageCalledCounter = 0;

        public function getHost()
        {
            return $this->host;
        }

        public function getPort()
        {
            return $this->port;
        }

        public function sendMessage($message)
        {
            $this->sendMessageCalledCounter++;
            $this->lastSentMessage = $message;
        }

        public function realSendMessage($message)
        {
            parent::sendMessage($message);
        }

        public function processMetric(MetricAbstract $metric)
        {
            $this->processMetricCalledCounter++;

            return parent::processMetric($metric);
        }

        public function getMetrics()
        {
            return $this->metrics;
        }

    }

    class StatsdSenderTest extends \PHPUnit_Framework_TestCase
    {

        public static $fsockopenReturn;
        public static $fsockopenCalled;
        public static $fsockopenLastAddress;
        public static $fsockopenLastPort;
        public static $fwriteException;
        public static $fwriteCalled;
        public static $fwriteLastString;
        public static $fcloseCalled;

        public $UDPMaxSize = 512;

        public function getMockStatsdSender()
        {
            return new MockStatsdSender(array(
                'host' => 'hostip',
                'port' => 54321,
                'udp_max_size' => $this->UDPMaxSize,
            ));
        }

        public function testStatsdSender()
        {
            $sender = $this->getMockStatsdSender();

            $this->assertEquals('hostip', $sender->getHost());
            $this->assertEquals(54321, $sender->getPort());
        }

        public function testSendMessage()
        {
            $sender = $this->getMockStatsdSender();

            self::$fsockopenReturn = true;
            self::$fsockopenCalled = false;
            self::$fwriteException = false;
            self::$fwriteCalled = false;
            self::$fcloseCalled = false;

            $sender->realSendMessage('barmessage');

            $this->assertTrue(self::$fsockopenCalled);
            $this->assertEquals('udp://hostip', self::$fsockopenLastAddress);
            $this->assertEquals(54321, self::$fsockopenLastPort);
            $this->assertTrue(self::$fwriteCalled);
            $this->assertEquals('barmessage', self::$fwriteLastString);
            $this->assertTrue(self::$fcloseCalled);
        }

        public function testSendMessageException()
        {
            $sender = $this->getMockStatsdSender();

            self::$fsockopenReturn = true;
            self::$fsockopenCalled = false;
            self::$fwriteException = true;
            self::$fwriteCalled = false;
            self::$fcloseCalled = false;

            $sender->realSendMessage('barmessage');

            $this->assertTrue(self::$fsockopenCalled);
            $this->assertTrue(self::$fwriteCalled);
            $this->assertFalse(self::$fcloseCalled);
        }

        public function testSendMessageFailed()
        {
            $sender = $this->getMockStatsdSender();

            self::$fsockopenReturn = false;
            self::$fsockopenCalled = false;
            self::$fwriteException = false;
            self::$fwriteCalled = false;
            self::$fcloseCalled = false;

            $sender->realSendMessage('barmessage');

            $this->assertTrue(self::$fsockopenCalled);
            $this->assertFalse(self::$fwriteCalled);
            $this->assertFalse(self::$fcloseCalled);
        }

        public function testSendMetric()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new CounterMetric('key');
            $message = $sender->processMetric($metric);
            $sender->sendMetric($metric);
            $this->assertEquals($message, $sender->lastSentMessage);
            $this->assertEquals(2, $sender->processMetricCalledCounter);
        }

        public function testSendMetrics()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new CounterMetric('key');
            $message = $sender->processMetric($metric);
            $sender->addMetric($metric);
            $sender->addMetric($metric);
            $sender->onKernelTerminate(new Event);
            $this->assertEquals($message . PHP_EOL . $message, $sender->lastSentMessage);
            $this->assertEquals(3, $sender->processMetricCalledCounter);
            $this->assertEquals(1, $sender->sendMessageCalledCounter);
        }

        public function testSendMetricsMultipleMessages()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new CounterMetric('key');
            $message = $sender->processMetric($metric);
            $len = strlen($message) + 1;
            $nb = ((int)($this->UDPMaxSize / $len)) + 1;
            for ($i = 0; $i < $nb; $i++) {
                $sender->addMetric($metric);
            }
            $sender->onKernelTerminate(new Event);
            $this->assertEquals($message, $sender->lastSentMessage);
            $this->assertEquals($nb + 1, $sender->processMetricCalledCounter);
            $this->assertEquals(2, $sender->sendMessageCalledCounter);
        }

        public function testFlushMetrics()
        {
            $sender = $this->getMockStatsdSender();
            $metric = new CounterMetric('key');
            $message = $sender->processMetric($metric);
            $sender->addMetric($metric);
            $this->assertEquals(1, count($sender->getMetrics()));
            $sender->flushMetrics();
            $this->assertEquals($message, $sender->lastSentMessage);
            $this->assertEquals(2, $sender->processMetricCalledCounter);
            $this->assertEquals(1, $sender->sendMessageCalledCounter);
            $this->assertEmpty($sender->getMetrics());
        }

        public function testProcessIncrementMetric()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new CounterMetric('key');
            $this->assertEquals('key:1|c', $sender->processMetric($metric));

            $metric->setSampleRate(0.3);
            $message = $sender->processMetric($metric);
            $i = 0;
            while ($message == false && $i < 50) {
                $message = $sender->processMetric($metric);
                $i++;
            }
            $this->assertEquals('key:1|c|@0.3', $message);

            $metric->setSampleRate(0.01);
            $message = $sender->processMetric($metric);
            $i = 0;
            while ($message != false && $i < 50) {
                $message = $sender->processMetric($metric);
                $i++;
            }
            $this->assertFalse($message);

            $metric->setSampleRate(1);
            $this->assertEquals('key:1|c', $sender->processMetric($metric));
        }

        public function testProcessGaugeMetric()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new GaugeMetric('key', 42);
            $this->assertEquals('key:42|g', $sender->processMetric($metric));
        }

        public function testProcessSetMetric()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new SetMetric('key', 42);
            $this->assertEquals('key:42|s', $sender->processMetric($metric));
        }

        public function testProcessTimerMetric()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new TimerMetric('key', 0.042);
            $this->assertEquals('key:0.042|ms', $sender->processMetric($metric));
        }

        public function testProcessMissingStatsdMetricCode()
        {
            $sender = $this->getMockStatsdSender();

            $metric = new TimerMetric('key', 0.042);
            $metric->setStatsdMetricCode('');

            $this->setExpectedException('Exception');
            $sender->processMetric($metric);
        }

    }

}