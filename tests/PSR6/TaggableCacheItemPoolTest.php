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
use Ness\Component\Cache\PSR6\CacheItemPool;
use Ness\Component\Cache\Adapter\InMemoryCacheAdapter;
use Ness\Component\Cache\Serializer\SerializerInterface;

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
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        TaggableCacheItemPool::unregisterSerializer();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        TaggableCacheItemPool::unregisterSerializer();
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::getItem()
     */
    public function testGetItem(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(2))
            ->method("unserialize")
            ->withConsecutive(["foo"], ["foo"])
            ->will($this->onConsecutiveCalls("foo", "foo"));
        
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("get")
                ->with($prefixation("foo", TaggableCacheItemPool::CACHE_FLAG."global_"))
                ->will($this->onConsecutiveCalls(
                    "{\"value\":\"foo\",\"ttl\":-1,\"saved\":[\"foo\",\"bar\"]}",
                    '{"value":"foo","ttl":-1}',
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
        $this->assertSame(["foo", "bar"], $taggable->getPreviousTags());
        $this->assertTrue($standard->isHit());
        $this->assertFalse($null->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::getItems()
     */
    public function testGetItems(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(2))
            ->method("unserialize")
            ->withConsecutive(["foo"], ["foo"])
            ->will($this->onConsecutiveCalls("foo", "foo"));
            
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->once())
                ->method("getMultiple")
                ->with([
                    $prefixation("foo", TaggableCacheItemPool::CACHE_FLAG."global_"),
                    $prefixation("bar", TaggableCacheItemPool::CACHE_FLAG."global_"),
                    $prefixation("moz", TaggableCacheItemPool::CACHE_FLAG."global_")
                ])
                ->will($this->returnValue(
                [
                    "{\"value\":\"foo\",\"ttl\":-1,\"saved\":[\"foo\",\"bar\"]}",
                    '{"value":"foo","ttl":-1}',
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
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::clear()
     */
    public function testClear(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $tagMap = function(MockObject $tagMap, CacheAdapterInterface $adapter): void {
            $tagMap->expects($this->once())->method("clear")->will($this->returnValue(true));
        };
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(CacheItemPool::CACHE_FLAG."global")->will($this->returnValue(true));
        });
        
        $pool = $this->getPool($adapter, $tagMap);
        
        $this->assertTrue($pool->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::save()
     */
    public function testSave(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $item = new TaggableCacheItem("foo");
        $item->setTags(["foo", "bar"]);
        $tagMap = function(MockObject $tagMap, CacheAdapterInterface $adapter) use ($item): void {
            $tagMap->expects($this->once())->method("save")->with(CacheItemPool::CACHE_FLAG."global_foo", ["foo", "bar"], false);
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $item = new TaggableCacheItem("foo");
        $item->setTags(["foo", "bar"]);
        $tagMap = function(MockObject $tagMap, CacheAdapterInterface $adapter) use ($item): void {
            $tagMap->expects($this->once())->method("save")->with(CacheItemPool::CACHE_FLAG."global_foo", ["foo", "bar"], true);
        };
        
        $pool = $this->getPool($this->getMockedAdapter(), $tagMap);
        
        $this->assertTrue($pool->saveDeferred(new CacheItem("foo")));
        $this->assertTrue($pool->saveDeferred($item));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::commit()
     */
    public function testCommit(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $tagMap = function(MockObject $tagMap, CacheAdapterInterface $adapter): void {
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $pool = $this->getPool($this->getMockedAdapter(), function(MockObject $tagMap, CacheAdapterInterface $adapter): void {
            $tagMap->expects($this->once())->method("delete")->with($adapter, "foo");
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
        
        $this->assertTrue($pool->invalidateTag("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::invalidateTags()
     */
    public function testInvalidateTags(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $pool = $this->getPool($this->getMockedAdapter(), function(MockObject $tagMap, CacheAdapterInterface $adapter): void {
            $tagMap->expects($this->exactly(4))->method("delete")->withConsecutive([$adapter, "foo"], [$adapter, "bar"], [$adapter, "moz"], [$adapter, "poz"]);
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
            
        $this->assertTrue($pool->invalidateTags(["foo", "bar", "moz", "poz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItemPool::invalidateTag()
     */
    public function testNamespaceIsolation(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->exactly(3))->method("unserialize")->withConsecutive(["bar"])->will($this->returnValue("bar"));
        TaggableCacheItemPool::registerSerializer($serializer);
        
        $adapter = new InMemoryCacheAdapter();
        $pool = new TaggableCacheItemPool($adapter);
        $poolNamespaced = new TaggableCacheItemPool($adapter, null, null, "foo");
        
        $itemFoo = $pool->getItem("foo")->set("bar")->setTags(["foo", "bar"]);
        $itemFooNamespaced = $poolNamespaced->getItem("foo")->set("bar")->setTags(["foo", "bar"]);
        
        $pool->save($itemFoo);
        $poolNamespaced->save($itemFooNamespaced);
        
        $this->assertSame("bar", $pool->getItem("foo")->get());
        $this->assertSame("bar", $poolNamespaced->getItem("foo")->get());
        
        $poolNamespaced->invalidateTag("foo");
        
        $this->assertSame("bar", $pool->getItem("foo")->get());
        $this->assertNull($poolNamespaced->getItem("foo")->get());
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
            $action->call($this, $map, $adapter);
        
        $reflection = new \ReflectionClass($pool);
        $property = $reflection->getProperty("tagMap");
        $property->setAccessible(true);
        $property->setValue($pool, $map);
        
        return $pool;
    }
    
}
