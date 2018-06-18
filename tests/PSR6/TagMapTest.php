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
use Ness\Component\Cache\PSR6\TagMap;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Cache\PSR6\TaggableCacheItem;

/**
 * TagMap testcase
 * 
 * @see \Ness\Component\Cache\PSR6\TagMap
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TagMapTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\PSR6\TagMap::initializeMap()
     */
    public function testInitializeMap(): void
    {
        $tags = ["foo" => ["foo_item", "bar_item"]];
        $serialized = \serialize($tags);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($serialized): void {
            $adapter->expects($this->exactly(2))->method("get")->withConsecutive([TagMap::TAGS_MAP_IDENTIFIER])->will($this->onConsecutiveCalls(null, $serialized));
        });
    
        $map = new TagMap();
        $map->setAdapter($adapter);
        
        $this->assertNull($map->initializeMap());
        $this->assertNull($map->initializeMap());
    }
        /**
     * @see \Ness\Component\Cache\PSR6\TagMap::delete()
     */
    public function testDelete(): void
    {
        $tags = [
            "foo"   =>  ["foo_item", "bar_item"],
            "bar"   =>  ["foo_item", "bar_item"]
        ];
        $newTags = [
            "bar"   =>  ["foo_item", "bar_item"]
        ];
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($tags, $newTags): void {
            $adapter->expects($this->once())->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER)->will($this->returnValue(\serialize($tags)));
            $adapter->expects($this->once())->method("set")->with(TagMap::TAGS_MAP_IDENTIFIER, \serialize($newTags), null)->will($this->returnValue(true));
        });
        
        $pool = $this->getMockBuilder(CacheItemPoolInterface::class)->getMock();
        $pool->expects($this->once())->method("deleteItems")->with(["foo_item", "bar_item"])->will($this->returnValue(true));
            
        $map = new TagMap();
        $map->setAdapter($adapter);
        $map->initializeMap();
        
        $this->assertNull($map->delete($pool, "foo"));
        $map->update(false);
        $this->assertNull($map->delete($pool, "moz"));
        $map->update(false);
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TagMap::save()
     * @see \Ness\Component\Cache\PSR6\TagMap::update()
     */
    public function testSaveAndUpdate(): void
    {
        $defaultMap = ["foo" => ["foo_item", "bar_item"]];
        $expectedUpdatedMap = \array_merge($defaultMap, ["bar" => ["foo_item"]]);
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($defaultMap, $expectedUpdatedMap): void {
            $adapter->expects($this->once())->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER)->will($this->returnValue(\serialize($defaultMap)));
            $adapter->expects($this->once())->method("set")->with(TagMap::TAGS_MAP_IDENTIFIER, \serialize($expectedUpdatedMap), null)->will($this->returnValue(true));
            
        });
        
        $item = $this->getMockBuilder(TaggableCacheItem::class)->disableOriginalConstructor()->getMock();
        $item->expects($this->exactly(4))->method("getKey")->will($this->returnValue("foo_item"));
        $item->expects($this->exactly(2))->method("getCurrent")->will($this->returnValue(["foo", "bar"]));
        
        $map = new TagMap();
        $map->setAdapter($adapter);
        $map->initializeMap();
        $this->assertNull($map->save($item, true));
        $this->assertTrue($map->update(false));
        // test when no extra tags has been added
        $this->assertNull($map->save($item, false));
        $this->assertTrue($map->update(true));
        $this->assertTrue($map->update(false));
        $this->assertTrue($map->update(true));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TagMap::update()
     */
    public function testUpdate(): void
    {
        $tagMap = new TagMap();
        
        $this->assertTrue($tagMap->update(false));
        $this->assertTrue($tagMap->update(true));
    }
    
    /**
     * 1@see \Ness\Component\Cache\PSR6\TagMap::setAdapter()
     */
    public function testSetAdapter(): void
    {
        $map = new TagMap();
        
        $this->assertNull($map->setAdapter($this->getMockedAdapter()));
    }
    
}
