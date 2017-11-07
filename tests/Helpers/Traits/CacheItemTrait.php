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

namespace ZoeTest\Component\Cache\Helpers\Traits;

use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Zoe\Component\Cache\CacheItem;

/**
 * Helpers to handle test that implied usage of CacheItem implementation
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
trait CacheItemTrait
{
    
    /**
     * Get a mocked cache item instance
     * 
     * @param string $key
     *   Key of the cache item (getKey)
     * @param mixed $value
     *   Value of the cache item (get)
     * @param bool $hit
     *   If the cache item is considered a cache hit
     * @param TestCase $case
     *   Test case context
     * @param int|null|float $ttl
     *   Time in seconds or null or \INF 
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked cache item
     */
    public function getMockedCacheItem(
        string $key, 
        $value, 
        bool $hit, 
        TestCase $case, 
        $ttl = INF): \PHPUnit_Framework_MockObject_MockObject
    {
        
        $reflection = new \ReflectionClass(CacheItemInterface::class);
        $methods = $this->reflection_extractMethods($reflection);
        $methods[] = "getExpiration";

        $mock = $case
                    ->getMockBuilder(CacheItemInterface::class)
                    ->setMethods($methods)
                    ->disableOriginalConstructor()
                    ->getMock();
                    
        $mock->method("getKey")->will($case->returnValue($key));
        $mock->method("get")->will($case->returnValue($value));
        
        if(null === $ttl)
            $mock->method("getExpiration")->will($case->returnValue(null));
        else if(\is_float($ttl) && \is_infinite($ttl))
            $mock->method("getExpiration")->will($case->returnValue(INF)); 
        else
            $mock->method("getExpiration")->will($case->returnValue(new \DateTime("NOW + {$ttl} seconds")));
        
        if($hit) {
            $mock->method("isHit")->will($case->returnValue(true));
        } else {
            $mock->method("isHit")->will($case->returnValue(false));
        }
        
        return $mock;
    }
    
    /**
     * Get a cache item instance
     * 
     * @param string $key
     *   Cache key
     * @param string $value
     *   Cache value
     * @param bool $hit
     *   Hit item
     * @param string $ttl
     *   Time to live
     * 
     * @return CacheItemInterface
     *   Cache item instance
     */
    public function getCacheItemInstance(string $key, string $value, bool $hit, $ttl = INF): CacheItemInterface
    {
        $item = (new CacheItem($key))->set($value);
        if(null === $ttl) {
            $item->expiresAfter(null);
        } else if ($ttl instanceof \DateTimeInterface) {
            $item->expiresAt($ttl);
        } else if ($ttl instanceof \DateInterval || \is_int($ttl)) {
            $item->expiresAfter($ttl);
        }
        $reflection = new \ReflectionClass($item);
        $property = $reflection->getProperty("isHit");
        $property->setAccessible(true);
        $property->setValue($item, $hit);
        
        return $item;
    }
    
}
