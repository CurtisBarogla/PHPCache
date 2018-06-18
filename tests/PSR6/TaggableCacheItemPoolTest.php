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

namespace NessTest\Component\Cache\PSR6;

use NessTest\Component\Cache\CacheTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\PSR6\TaggableCacheItemPool;
use Cache\TagInterop\TaggableCacheItemInterface;
use Ness\Component\Cache\PSR6\TagMap;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\PSR6\CacheItem;
use Ness\Component\Cache\PSR6\TaggableCacheItem;

/**
 * TaggableCacheItemPool testcase
 * 
 * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCacheItemPoolTest extends CacheTestCase
{
 
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::__construct()
     */
    public function test__construct(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER);    
        });
        
        $tagsAdapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER);
        });
        
        $pool = new TaggableCacheItemPool($adapter);
        $pool = new TaggableCacheItemPool($adapter, $tagsAdapter);
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::getItem()
     */
    public function testGetItem(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("get")
                ->with($prefixation("foo", TaggableCacheItemPool::CACHE_FLAG))
                ->will($this->onConsecutiveCalls(
                    'C:43:"Ness\Component\Cache\PSR6\TaggableCacheItem":96:{a:6:{i:0;s:3:"foo";i:1;s:3:"foo";i:2;b:1;i:3;d:INF;i:4;a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}i:5;N;}}',
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"foo";i:2;b:1;i:3;d:INF;}}',
                    null
                ));  
        });
        
        $pool = new TaggableCacheItemPool($adapter, $this->getMockedAdapter());
        
        $taggable = $pool->getItem("foo");
        $standard = $pool->getItem("foo");
        $null = $pool->getItem("foo");
        
        foreach ([$taggable, $standard, $null] as $item) {
            $this->assertInstanceOf(TaggableCacheItemInterface::class, $item);
            $this->assertSame("foo", $item->getKey());
        }
        
        $this->assertTrue($taggable->isHit());
        $this->assertTrue($standard->isHit());
        $this->assertFalse($null->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::getItems()
     */
    public function testGetItems(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->once())
                ->method("getMultiple")
                ->with([
                    $prefixation("foo", TaggableCacheItemPool::CACHE_FLAG),
                    $prefixation("bar", TaggableCacheItemPool::CACHE_FLAG),
                    $prefixation("moz", TaggableCacheItemPool::CACHE_FLAG)
                ])
                ->will($this->returnValue(
                [
                    'C:43:"Ness\Component\Cache\PSR6\TaggableCacheItem":96:{a:6:{i:0;s:3:"foo";i:1;s:3:"foo";i:2;b:1;i:3;d:INF;i:4;a:2:{i:0;s:3:"foo";i:1;s:3:"bar";}i:5;N;}}',
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"foo";i:2;b:1;i:3;d:INF;}}',
                    null
                ]));
        });
        
        $pool = new TaggableCacheItemPool($adapter, $this->getMockedAdapter());
        
        $items = $pool->getItems(["foo", "bar", "moz"]);
        
        foreach ($items as $item) {
            $this->assertInstanceOf(TaggableCacheItemInterface::class, $item);
        }
        
        $this->assertTrue($items["foo"]->isHit());
        $this->assertTrue($items["bar"]->isHit());
        $this->assertFalse($items["moz"]->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::save()
     */
    public function testSave(): void
    {
        $item = new TaggableCacheItem("foo");
        $tagMap = function(MockObject $tagMap, TaggableCacheItemPool $pool) use ($item): void {
            $tagMap->expects($this->once())->method("save")->with($item, false);
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        };
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("set")->will($this->returnValue(true));  
        });
        
        $pool = $this->getPool($adapter, $tagMap);
        
        $this->assertTrue($pool->save(new CacheItem("foo")));
        $this->assertTrue($pool->save($item));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::saveDeferred()
     */
    public function testSaveDeferred(): void
    {
        $item = new TaggableCacheItem("foo");
        $tagMap = function(MockObject $tagMap, TaggableCacheItemPool $pool) use ($item): void {
            $tagMap->expects($this->once())->method("save")->with($item, true);
        };
        
        $pool = $this->getPool($this->getMockedAdapter(), $tagMap);
        
        $this->assertTrue($pool->saveDeferred(new CacheItem("foo")));
        $this->assertTrue($pool->saveDeferred(new TaggableCacheItem("foo")));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::commit()
     */
    public function testCommit(): void
    {
        $tagMap = function(MockObject $tagMap, TaggableCacheItemPool $pool): void {
            $tagMap->expects($this->once())->method("update")->with(true)->will($this->returnValue(true));
        };
            
        $pool = $this->getPool($this->getMockedAdapter(), $tagMap);
        
        $this->assertTrue($pool->commit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::invalidateTag()
     */
    public function testInvalidateTag(): void
    {
        $pool = $this->getPool($this->getMockedAdapter(), function(MockObject $tagMap, TaggableCacheItemPool $pool): void {
            $tagMap->expects($this->once())->method("delete")->with($pool, "foo");
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
        
        $this->assertTrue($pool->invalidateTag("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::invalidateTags()
     */
    public function testInvalidateTags(): void
    {
        $pool = $this->getPool($this->getMockedAdapter(), function(MockObject $tagMap, TaggableCacheItemPool $pool): void {
            $tagMap->expects($this->exactly(4))->method("delete")->withConsecutive([$pool, "foo"], [$pool, "bar"], [$pool, "moz"], [$pool, "poz"]);
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
            
        $this->assertTrue($pool->invalidateTags(["foo", "bar", "moz", "poz"]));
    }
    
    /**
     * Get a taggable cache item pool with a setted mocked tag map instance setted into it
     * 
     * @param CacheAdapterInterface $adapter
     *   Adapter setted for the pool
     * @param \Closure|null $action
     *   Action done on the tag map
     * 
     * @return TaggableCacheItemPool
     *   TaggableCacheItemPool with tag map setted
     */
    private function getPool(CacheAdapterInterface $adapter, ?\Closure $action = null): TaggableCacheItemPool
    {
        $map = $this->getMockBuilder(TagMap::class)->disableOriginalConstructor()->getMock();
        $pool = new TaggableCacheItemPool($adapter);
        if(null !== $action)
            $action->call($this, $map, $pool);
        
        $reflection = new \ReflectionClass($pool);
        $property = $reflection->getProperty("tagMap");
        $property->setAccessible(true);
        $property->setValue($pool, $map);
        
        return $pool;
    }
    
}
