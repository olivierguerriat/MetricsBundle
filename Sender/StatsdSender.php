<?php

namespace Guerriat\MetricsBundle\Sender;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\Config\Definition\Exception\Exception;

use Guerriat\MetricsBundle\Metric\MetricAbstract;

/**
 * Models a statsd sender
 * @package Guerriat\MetricsBundle\Sender
 */
class StatsdSender extends SenderAbstract
{

    /**
     * @var UDP payload max size
     */
    protected $UDPMaxSize;

    /**
     * @var string statsd host
     */
    protected $host;

    /**
     * @var int statsd port
     */
    protected $port;

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->host = $config['host'];
        $this->port = $config['port'];
        $this->UDPMaxSize = $config['udp_max_size'];
    }

    /**
     * {@inheritDoc}
     */
    public function sendMetric(MetricAbstract $metric)
    {
        $message = $this->processMetric($metric);
        if ($message) {
            $this->sendMessage($message);
        }
    }

    /**
     * Send multiple metrics, combining as much as possible in a single UDP datagram
     * @param array $metrics
     */
    public function sendMetrics(array $metrics)
    {
        $separator = PHP_EOL;
        $sepLen = strlen($separator);
        $maxLen = $this->UDPMaxSize + $sepLen; // The first separator is deleted just before sending
        $message = '';
        foreach ($metrics as $metric) {
            $msg = $this->processMetric($metric);
            if ($msg) {
                if ((strlen($message) + strlen($msg) + $sepLen) > $maxLen) {
                    $message = substr($message, $sepLen);
                    $this->sendMessage($message);
                    $message = '';
                }
                $message .= $separator . $msg;
            }
        }
        if (!empty($message)) {
            $message = substr($message, $sepLen);
            $this->sendMessage($message);
        }
    }

    /**
     * Send the message to the statsd server via UDP
     * @param string $message
     */
    protected function sendMessage($message)
    {
        if (!empty($message)) {
            // Silently ignore failures
            try {
                $fp = fsockopen('udp://' . $this->host, $this->port, $errno, $errstr);
                if (!$fp) {
                    return;
                }
                fwrite($fp, $message);
                fclose($fp);
            } catch (\Exception $e) {
            }
        }
    }

    /**
     * Return the statsd message or false if we can skip this Metric (when using a sample rate < 1)
     * @param MetricAbstract $metric
     * @throws \Exception
     * @return mixed message
     */
    protected function processMetric(MetricAbstract $metric)
    {
        $code = $metric->getStatsdMetricCode();
        if (empty($code)) {
            throw new \Exception('Missing statsd metric code for metric "'.$metric->getKey().'".');
        }
        if (method_exists($metric, 'getSampleRate') && $metric->getSampleRate() < 1) {
            if ((mt_rand() / mt_getrandmax()) > $metric->getSampleRate()) {
                return false;
            } else {
                return sprintf('%s:%s|%s|@%s', $metric->getKey(), $metric->getValue(), $code, $metric->getSampleRate());
            }
        }

        return sprintf('%s:%s|%s', $metric->getKey(), $metric->getValue(), $code);
    }

}


