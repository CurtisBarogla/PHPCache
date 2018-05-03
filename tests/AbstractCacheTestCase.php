<?php
//StrictType
declare(strict_types = 1);

/*
 * Zoe
 * Cache component
 *
 * Author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */

namespace ZoeTest\Component\Cache;

use PHPUnit\Framework\TestCase;

/**
 * Common to all test cases declared into cache component
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractCacheTestCase extends TestCase implements GlobalConfiguration
{
    
    /**
     * Enum long test
     * 
     * @var int
     */
    protected const LONG = 1;
    
    /**
     * Enum stupid test
     * 
     * @var int
     */
    protected const STUPID = 2;
    
    /**
     * Declare a test long or stupid (for now).
     * Will be skipped is not declared as executable into the GlobalConfiguration
     * 
     * @param string $type
     *   See enum into class
     */
    protected function _testIs(int $type): void
    {
        switch ($type) {
            case self::LONG:
                if(!self::EXECUTE_LONG_TESTS)
                    $this->markTestSkipped();
                break;
            case self::STUPID:
                if(!self::EXECUTE_STUPID_TESTS)
                    $this->markTestSkipped();
                break;
            default:
                return;
        }
    }
    
}
