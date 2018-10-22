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
 * Format logs from the LoggingWrapper
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
interface LogFormatterInterface
{
    
    /**
     * Format a log message from a cache adapter via the LoggingWrapper
     * 
     * @param string $component
     *   Component name
     * @param string $error
     *   Error message
     * @param \DateTime $when
     *   When the error happen
     * 
     * @return string
     *   A formatted representation of the log
     */
    public function format(string $component, string $error, \DateTime $when): string;
    
}
