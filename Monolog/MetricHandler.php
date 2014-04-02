<?php

namespace Guerriat\MetricsBundle\Monolog;

use Monolog\Handler\AbstractProcessingHandler;
use Guerriat\MetricsBundle\Metric\KeyFormatter;

/**
 * @inspiration liuggio/StatsDClientBundle
 */
class MetricHandler extends AbstractProcessingHandler
{

    protected $client;
    protected $prefix;

    /**
     * @param Client $client the associated client
     * @param string $prefix the key prefix
     * @param int $level the minimum level needed to be sent
     */
    public function __construct($client, $prefix, $level = Logger::DEBUG)
    {
        parent::__construct($level);
        $this->client = $client;
        $this->prefix = KeyFormatter::format($prefix, false, false, '-', '.');;
    }

    /**
     * Process log messages and send them to a transport
     * @param array $record
     */
    public function write(array $record)
    {
        $records = is_array($record['formatted']) ? $record['formatted'] : array($record['formatted']);

        foreach ($records as $record) {
            if (!empty($record)) {
                $this->client->increment($this->prefix . '.' . $record);
            }
        }
    }

}