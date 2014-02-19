<?php

namespace Guerriat\MetricsBundle\Monolog;

use Monolog\Formatter\LineFormatter;
use Guerriat\MetricsBundle\Metric\KeyFormatter;

/**
 * Formats records as valid statsd key
 * 
 * @inspiration liuggio/StatsDClientBundle
 */
class MetricFormatter extends LineFormatter
{
    const SIMPLE_FORMAT = "%channel%.%level_name%.%short_message%";

    protected $numberOfWords;
    protected $logContext;
    protected $logExtra;
    protected $format;

    /**
     * @param string $format The format of the message
     * @param Boolean $logContext If true add multiple rows containing Context information
     * @param Boolean $logExtra If true add multiple rows containing Extra information
     * @param integer $numberOfWords The number of words to show.
     */
    public function __construct($format = null, $logContext = true, $logExtra = true, $numberOfWords = 2)
    {
        parent::__construct();
        $this->format = $format ? $format : static::SIMPLE_FORMAT;
        $this->numberOfWords = $numberOfWords;
        $this->logContext = $logContext;
        $this->logExtra = $logExtra;
    }

    /**
     * This function converts a long message into a string with the first N-words.
     * eg. from: "Notified event 'kernel.request' to listener "Symfony\Component\HttpKernel\EventListener"
     * to: "Notified-event"
     *
     * @param string $message The message to shortify.
     * @return string
     */
    public function getFirstWords($message)
    {
        return KeyFormatter::format($message, $this->numberOfWords);
    }

    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        $vars = $this->normalize($record);

        $firstRow = $this->format;
        $output = array();

        $vars['short_message'] = $this->getFirstWords($vars['message']);
        foreach ($vars as $var => $val) {
            $firstRow = str_replace('%' . $var . '%', $this->convertToString($val), $firstRow);
        }
        $output[] = $firstRow;
        // creating more rows for context content
        if ($this->logContext && isset($vars['context'])) {
            foreach ($vars['context'] as $key => $parameter) {
                $output[] = sprintf("%s.context.%s.%s", $firstRow, $key, $parameter);
            }
        }
        // creating more rows for extra content
        if ($this->logExtra && isset($vars['extra'])) {
            foreach ($vars['extra'] as $key => $parameter) {
                $output[] = sprintf("%s.extra.%s.%s", $firstRow, $key, $parameter);
            }
        }

        return $output;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        $output = array();
        foreach ($records as $record) {
            $output = array_merge($output, $this->format($record));
        }

        return $output;
    }
    
}