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

use Zoe\Component\Cache\CachePool;
use Zoe\Component\Cache\Adapter\CacheAdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\Cache\Item\CacheItem;

/**
 * CachePool testcase
 * 
 * @see \Zoe\Component\Cache\CachePool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CachePoolTest extends AbstractCacheTestCase
{
    
    public function testGetItem(): void
    {
        $itemFromPool = new CacheItem("foo");
        $itemFromPool->set(["foo"]);
        $itemFromPool->setHit();
        $actions = function(MockObject $adapter, callable $prefix) use ($itemFromPool): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("get")
                ->withConsecutive([$prefix("foo")], [$prefix("bar")])
                ->will($this->onConsecutiveCalls(\serialize($itemFromPool), null));
        };
        
        $pool = $this->initializeCachePool($actions);
        
        $item = $pool->getItem("foo");
        $this->assertSame("foo", $item->getKey());
        $this->assertSame(["foo"], $item->get());
        $this->assertTrue($item->isHit());
        
        $item = $pool->getItem("bar")->set("foo");
        $this->assertSame("bar", $item->getKey());
        $this->assertSame("foo", $item->get());
        $this->assertFalse($item->isHit());
        
        $itemDeferred = new CacheItem("moz");
        
        $pool->saveDeferred($itemDeferred);
        $this->assertSame($itemDeferred, $pool->getItem("moz"));
    }
    
    public function testGetItems(): void
    {
        
    }
    
    public function testHasItem(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter->expects($this->exactly(2))->method("exists")->withConsecutive([$prefix("foo")], [$prefix("bar")])->will($this->onConsecutiveCalls(true, false));   
        };
        
        $pool = $this->initializeCachePool($actions);
        
        $this->assertTrue($pool->hasItem("foo"));
        $this->assertFalse($pool->hasItem("bar"));
    }
    
    public function testClear(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter->expects($this->once())->method("clear")->with(CachePool::PSR6_CACHE_FLAG)->will($this->returnValue(true));   
        };
        
        $pool = $this->initializeCachePool($actions);
        
        $this->assertTrue($pool->clear());
    }
    
    public function testDeleteItem(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter->expects($this->exactly(2))->method("delete")->withConsecutive([$prefix("foo")], [$prefix("bar")])->will($this->onConsecutiveCalls(true, false));   
        };
        
        $pool = $this->initializeCachePool($actions);
        
        $this->assertTrue($pool->deleteItem("foo"));
        $this->assertFalse($pool->deleteItem("bar"));
    }
    
    public function testDeleteItems(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("deleteMultiple")
                ->withConsecutive(
                    [
                        [$prefix("foo"), $prefix("bar")]
                    ],
                    [
                        [$prefix("foo"), $prefix("bar")]
                    ]
            )->will($this->onConsecutiveCalls(null, [$prefix("foo")]));   
        };
        
        $pool = $this->initializeCachePool($actions);
        
        $this->assertTrue($pool->deleteItems(["foo", "bar"]));
        $this->assertFalse($pool->deleteItems(["foo", "bar"]));
    }
    
    public function testSave(): void
    {
         
    }
    
    public function testSaveDeferred(): void
    {
        $pool = $this->initializeCachePool();
        
        $this->assertTrue($pool->saveDeferred($pool->getItem("foo")));
        $this->assertTrue($pool->saveDeferred($pool->getItem("bar")->expiresAfter(120)));
    }
    
    public function testCommit(): void
    {
        
    }
    
    
    /**
     * Initialize a new cache pool with an adapter setted
     * 
     * @param \Closure $actions
     *   Actions to call on the mocked adapter. Takes as parameters the mocked adapter and a helper to prefix key
     * 
     * @return CachePool
     *   Cache pool with an adapter setted
     */
    private function initializeCachePool(?\Closure $actions = null): CachePool
    {
        $adapter = $this->getMockBuilder(CacheAdapterInterface::class)->getMock();
        if(null !== $actions) {
            $prefix = function (string $key): string {
                return CachePool::PSR6_CACHE_FLAG.$key;   
            };
            $actions->call($this, $adapter, $prefix);
        }
        
        return new CachePool($adapter);
    }
    
    
}
