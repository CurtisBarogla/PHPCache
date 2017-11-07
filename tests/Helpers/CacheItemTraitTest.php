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

namespace ZoeTest\Component\Cache\Helpers;

use Psr\Cache\CacheItemInterface;
use ZoeTest\Component\Cache\GlobalConfiguration;
use ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait;

/**
 * CacheItemTrait testcase
 * 
 * @see \ZoeTest\Component\Cache\Helpers\CacheItemTrait
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemTraitTest
{
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getMockedCacheItem()
     */
    public function testGetMockedCacheItem(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getMockedCacheItem("foo", "bar", false, $this);
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertSame("foo", $item->getKey());
        $this->assertSame("bar", $item->get());
        $this->assertFalse($item->isHit());
        $this->assertInfinite($item->getExpiration());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getMockedCacheItem()
     */
    public function testGetMockedCacheItemWithHit(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getMockedCacheItem("foo", "bar", true, $this);

        $this->assertTrue($item->isHit());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getMockedCacheItem()
     */
    public function testGetMockedCacheItemWithTtlInSecondsGiven(): void
    {        
        $mock = $this->getMockedTrait();
        $item = $mock->getMockedCacheItem("foo", "bar", true, $this, 10);
        
        $this->assertInstanceOf(\DateTimeInterface::class, $item->getExpiration());
        $this->assertSame(
            (new \DateTime("NOW + 10 seconds"))->format(GlobalConfiguration::DATE_FORMAT_TEST), 
            $item->getExpiration()->format(GlobalConfiguration::DATE_FORMAT_TEST));
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getMockedCacheItem()
     */
    public function testGetMockedCacheItemWhenNullIsExplicityGivenAsExpiration(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getMockedCacheItem("foo", "bar", true, $this, null);
        
        $this->assertNull($item->getExpiration());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getCacheItemInstance()
     */
    public function testGetCacheItemInstanceWithHitSettedToTrue(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", true);
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertTrue($item->isHit());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getCacheItemInstance()
     */
    public function testGetCacheItemWithHitSettedToFalse(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", false);
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertFalse($item->isHit());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getCacheItemInstance()
     */
    public function testGetCacheItemWithNullIsExplicitlySetted(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", false, null);
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertFalse($item->isHit());
        $this->assertNull($item->getExpiration());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getCacheItemInstance()
     */
    public function testGetCacheItemWithIntOrDateIntervalGiven(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", false, \DateInterval::createFromDateString("P1D"));
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertInstanceOf(\DateTimeInterface::class, $item->getExpiration());
        
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", false, 1);
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertInstanceOf(\DateTimeInterface::class, $item->getExpiration());
    }
    
    /**
     * @see \ZoeTest\Component\Cache\Helpers\Traits\CacheItemTrait::getCacheItemInstance()
     */
    public function testGetCacheItemWithDateTimeInterfaceGiven(): void
    {
        $mock = $this->getMockedTrait();
        $item = $mock->getCacheItemInstance("foo", "bar", false, new \DateTime("NOW + 1 second"));
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
        $this->assertInstanceOf(\DateTimeInterface::class, $item->getExpiration());
    }
    
    /**
     * Mock the CacheItemTrait and return it
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked object
     */
    private function getMockedTrait(): \PHPUnit_Framework_MockObject_MockObject
    {
        $mock = $this->getMockForTrait(CacheItemTrait::class);
        
        return $mock;
    }
    
}
