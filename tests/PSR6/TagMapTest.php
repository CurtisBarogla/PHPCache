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
     * @see \Ness\Component\Cache\PSR6\TagMap::delete()
     */
    public function testDelete(): void
    {
        $tags = [
            "foo"   =>  ["foo_item", "bar_item"],
            "bar"   =>  ["foo_item", "bar_item"],
            "poz"   =>  ["foo_item", "poz_item"]
        ];
        $newTags = [
            "poz"   =>  ["poz_item"]
        ];
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($tags, $newTags): void {
            $adapter->expects($this->exactly(2))->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo")->will($this->returnValue(\json_encode($tags)));
            $adapter->expects($this->once())->method("deleteMultiple")->with(["foo_item", "bar_item"])->will($this->returnValue(null));
            $adapter->expects($this->once())->method("set")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo", \json_encode($newTags), null)->will($this->returnValue(true));
        });
          
        $map = new TagMap();
        $map->setAdapter($adapter);
        $map->setNamespace("foo");
        
        $this->assertNull($map->delete($adapter, "foo"));
        $map->update(false);
        $this->assertNull($map->delete($adapter, "moz"));
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
            $adapter->expects($this->exactly(4))->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo")->will($this->returnValue(\json_encode($defaultMap)));
            $adapter->expects($this->once())->method("set")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo", \json_encode($expectedUpdatedMap), null)->will($this->returnValue(true));
            
        });

        $map = new TagMap();
        $map->setAdapter($adapter);
        $map->setNamespace("foo");
        
        $this->assertNull($map->save("foo_item", ["bar"], true));
        $this->assertTrue($map->update(false));
        // test when no extra tags has been added
        $this->assertNull($map->save("foo_item", ["bar"], false));
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
     * @see \Ness\Component\Cache\PSR6\TagMap::setAdapter()
     */
    public function testSetAdapter(): void
    {
        $map = new TagMap();
        
        $this->assertNull($map->setAdapter($this->getMockedAdapter()));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TagMap::setNamespace()
     */
    public function testSetNamespace(): void
    {
        $map = new TagMap();
        
        $this->assertNull($map->setNamespace("foo"));
    }
    
}
