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

namespace NessTest\Component\Cache\PSR16;

use NessTest\Component\Cache\CacheTestCase;
use Ness\Component\Cache\PSR16\TaggableCache;
use Ness\Component\Cache\Tag\TagMap;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\Serializer\SerializerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\PSR16\Cache;
use Ness\Component\Cache\Exception\InvalidArgumentException;

/**
 * TaggagleCache testcase
 * 
 * @see \Ness\Component\Cache\PSR16\TaggableCache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCacheTest extends CacheTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        TaggableCache::unregisterSerializer();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        TaggableCache::unregisterSerializer();
        TaggableCache::$gcTapMap = 20;
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::set()
     */
    public function testSet(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("set")->will($this->returnValue(true));
        });
        
        $cache = $this->getCache($adapter, function(MockObject $tagMap, MockObject $adapter): void {
             $tagMap->expects($this->once())->method("save")->with(TaggableCache::CACHE_FLAG."global_foo", ["foo", "bar"], false);
             $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
        
        $this->assertTrue($cache->set("foo", "bar"));
        $this->assertTrue($cache->set("foo", "bar", null, ["foo", "bar"]));
    }
    
    public function testClear(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $tagMap = function(MockObject $tagMap, MockObject $adapter): void {
            $tagMap->expects($this->once())->method("clear")->will($this->returnValue(true));
        };
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(Cache::CACHE_FLAG."global")->will($this->returnValue(true));
        });
            
        $cache = $this->getCache($adapter, $tagMap);
        
        $this->assertTrue($cache->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("setMultiple")->will($this->returnValue(null));
        });
            
        $cache = $this->getCache($adapter, function(MockObject $tagMap, MockObject $adapter): void {
            $tagMap
                ->expects($this->exactly(4))
                ->method("save")
                ->withConsecutive(
                    [TaggableCache::CACHE_FLAG."global_foo", ["foo", "bar"], false],
                    [TaggableCache::CACHE_FLAG."global_bar", ["foo", "bar"], false],
                    [TaggableCache::CACHE_FLAG."global_moz", ["foo", "bar"], false],
                    [TaggableCache::CACHE_FLAG."global_poz", ["foo", "bar"], false]);
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
                
        $this->assertTrue($cache->setMultiple(["foo" => "bar"]));
        $this->assertTrue($cache->setMultiple([
            "foo"   =>  "bar",
            "bar"   =>  "foo",
            "moz"   =>  "poz",
            "poz"   =>  "moz"
        ], null, ["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::invalidateTag()
     */
    public function testInvalidateTag(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $cache = $this->getCache($this->getMockedAdapter(), function(MockObject $tagMap, MockObject $adapter): void {
            $tagMap->expects($this->once())->method("delete")->with($adapter, "foo", 20);
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
            
        $this->assertTrue($cache->invalidateTag("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::invalidateTags()
     */
    public function testInvalidateTags(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        TaggableCache::$gcTapMap = 30;
        
        $cache = $this->getCache($this->getMockedAdapter(), function(MockObject $tagMap, MockObject $adapter): void {
            $tagMap
            ->expects($this->exactly(4))
            ->method("delete")
            ->withConsecutive(
                [$adapter, "foo", 30],
                [$adapter, "bar", 30],
                [$adapter, "moz", 30],
                [$adapter, "poz", 30]
            );
            $tagMap->expects($this->once())->method("update")->with(false)->will($this->returnValue(true));
        });
            
        $this->assertTrue($cache->invalidateTags(["foo", "bar", "moz", "poz"]));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::set()
     */
    public function testExceptionSetWhenInvalidTagIsGiven(): void
    {
        $invalidTag = \str_repeat("foo", 32);
        $tags = ["foo", "bar", $invalidTag];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This tag '{$invalidTag}' is invalid. Tag length MUST be < 32 and contains only alphanum characters");
        
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $cache = $this->getCache($this->getMockedAdapter(null), null);
        
        $cache->set("foo", "bar", -1, $tags);
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\TaggableCache::setMultiple()
     */
    public function testExceptionSetMultipleWhenInvalidTagIsGiven(): void
    {
        $invalidTag = "foo@";
        $tags = ["foo", "bar", $invalidTag];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This tag '{$invalidTag}' is invalid. Tag length MUST be < 32 and contains only alphanum characters");
        
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        TaggableCache::registerSerializer($serializer);
        
        $cache = $this->getCache($this->getMockedAdapter(null), null);
        
        $cache->setMultiple(["foo" => "bar"], -1, $tags);
    }
    
    /**
     * Get an initialized cache instance
     * 
     * @param CacheAdapterInterface $adapter
     *   Adapter to set into the cache
     * @param \Closure|null $action
     *   Action to perform on the tag map
     * 
     * @return TaggableCache
     *   Taggable cache initialized
     */
    private function getCache(CacheAdapterInterface $adapter, ?\Closure $action = null): TaggableCache
    {
        $map = $this->getMockBuilder(TagMap::class)->disableOriginalConstructor()->getMock();
        $cache = new TaggableCache($adapter);
        if(null !== $action)
            $action->call($this, $map, $adapter);
        
        $reflection = new \ReflectionClass($cache);
        $property = $reflection->getProperty("tagMap");
        $property->setAccessible(true);
        $property->setValue($cache, $map);
        
        return $cache;
    }
    
}
