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

namespace NessTest\Component\Cache;

use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Common to all tests for Cache component
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class CacheTestCase extends TestCase
{
    
    /**
     * If tests implying usage of sleep (or other...) should be executed
     *
     * @var bool
     */
    private const EXECUTE_LONG_TESTS = true;
    
    /**
     * Get an mocked CacheAdapter
     *
     * @param \Closure $action
     *   Action done on the mock object before returned.
     *   Take as parameters the MockObject adapter and a helper to prefix keys
     *
     * @return MockObject
     *   Mocked CacheAdapter
     */
    protected function getMockedAdapter(?\Closure $action = null): MockObject
    {
        $adapter = $this->getMockBuilder(CacheAdapterInterface::class)->getMock();
        if(null !== $action) {
            $prefixation = function($keys, string $prefix) {
                if(\is_array($keys)) {
                    return \array_map(function(string $key) use ($prefix) {
                        return [$prefix.$key];
                    }, $keys);
                }
                
                return $prefix.$keys;
            };
            $action->call($this, $adapter, $prefixation);
        }
        
        return $adapter;
    }
    
    /**
     * Mark a test as long and therefore not executable if configuration long test if setted to false
     */
    protected function markAsLong(): void
    {
        if(!self::EXECUTE_LONG_TESTS)
            $this->markTestSkipped("Long tests are not executed");
    }
    
}
