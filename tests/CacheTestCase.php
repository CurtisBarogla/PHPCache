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
use Zoe\Component\Cache\Adapter\AdapterInterface;

/**
 * Grant access to global configuration and common methods
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTestCase extends TestCase implements GlobalConfiguration
{
    
    /**
     * Get a mocked instance of an AdapterInterface
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked AdapterInterface instance
     */
    protected function getMockedAdapter(): \PHPUnit_Framework_MockObject_MockObject
    {
        $methods = [
            "get", "getMultiple", "set", "setMultiple", "del", "delMultiple", "exists", "clear"
        ];
        
        $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
        
        return $mock;
    }
    
}