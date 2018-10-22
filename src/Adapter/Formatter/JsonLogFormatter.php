<?php
//StrictType
declare(strict_types = 1);

/*
 * Ness
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace Ness\Component\Cache\Adapter\Formatter;

/**
 * Simply jsonify the log
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonLogFormatter implements LogFormatterInterface
{
    
    /**
     * Date format to user
     * 
     * @var string
     */
    private $dateFormat;
    
    /**
     * Initialize log formatter
     * 
     * @param string $dateFormat
     *   Date format to apply for displaying the date of the log
     */
    public function __construct(string $dateFormat = \DateTime::ATOM)
    {
        $this->dateFormat = $dateFormat;
    }
    
    /**
     * {@inheritDoc}
     * @see \Ness\Component\Cache\Adapter\Formatter\LogFormatterInterface::format()
     */
    public function format(string $component, string $error, \DateTime $when): string
    {
        return \json_encode([
            "component"     =>  $component,
            "message"       =>  $error,
            "time"          =>  $when->format($this->dateFormat)
        ]);
    }
    
}
