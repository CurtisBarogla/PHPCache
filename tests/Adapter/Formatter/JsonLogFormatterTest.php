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

namespace NessTest\Component\Cache\Adapter\Formatter;

use NessTest\Component\Cache\CacheTestCase;
use Ness\Component\Cache\Adapter\Formatter\JsonLogFormatter;

/**
 * JsonLogFormatter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\Formatter\JsonLogFormatter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class JsonLogFormatterTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\Adapter\Formatter\JsonLogFormatter
     */
    public function testFormat(): void
    {
        $time = new \DateTime();
        $formatTime = $time->format(\DateTime::ATOM);
        
        $formatter = new JsonLogFormatter();
        
        $normalized = $formatter->format("[foo]", "FooError", $time);
        $denormalized = \json_decode($normalized, true);
        $this->assertSame("[foo]", $denormalized["component"]);
        $this->assertSame("FooError", $denormalized["message"]);
        $this->assertSame($formatTime, $denormalized["time"]);
    }
    
}
