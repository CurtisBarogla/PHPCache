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
use Zoe\Component\Internal\GeneratorTrait;
use Zoe\Component\Internal\ReflectionTrait;
use ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait;

/**
 * Grant access to global configuration and common methods
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTestCase extends TestCase implements GlobalConfiguration
{
    
    use GeneratorTrait;
    use ReflectionTrait;
    use CacheItemTrait;
    
    /**
     * Get a mocked instance of an AdapterInterface
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked AdapterInterface instance
     */
    protected function getMockedAdapter(): \PHPUnit_Framework_MockObject_MockObject
    {
        $reflection = new \ReflectionClass(AdapterInterface::class);
        $methods = $this->reflection_extractMethods($reflection);
        
        $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
        
        return $mock;
    }
    
}
