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

namespace NessTest\Component\Cache\Tag;

use NessTest\Component\Cache\CacheTestCase;
use Ness\Component\Cache\Tag\TagMap;
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
     * @see \Ness\Component\Cache\Tag\TagMap::delete()
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
        
        $this->assertNull($map->delete($adapter, "foo", 100));
        $map->update(false);
        $this->assertNull($map->delete($adapter, "moz", 100));
        $map->update(false);
    }
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMap::save()
     * @see \Ness\Component\Cache\Tag\TagMap::update()
     */
    public function testSaveAndUpdate(): void
    {
        $defaultMap = ["foo" => ["foo_item", "bar_item"]];
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($defaultMap): void {
            $adapter->expects($this->exactly(2))->method("get")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo")->will($this->returnValue(\json_encode($defaultMap)));
            $adapter
                ->expects($this->exactly(2))
                ->method("set")
                ->withConsecutive(
                    [TagMap::TAGS_MAP_IDENTIFIER."_foo", \json_encode(\array_merge($defaultMap, ["bar" => ["foo_item"]])), null],
                    [TagMap::TAGS_MAP_IDENTIFIER."_foo", \json_encode(\array_merge($defaultMap, ["moz" => ["foo_item"]])), null])
                ->will($this->onConsecutiveCalls(true, false));
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
        
        $this->assertNull($map->save("foo_item", ["moz"], false));
        $this->assertFalse($map->update(false));
    }
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMap::clear()
     */
    public function testClear(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("delete")->with(TagMap::TAGS_MAP_IDENTIFIER."_foo")->will($this->returnValue(true));
        });
        
        $tagMap = new TagMap();
        $tagMap->setAdapter($adapter);
        $tagMap->setNamespace("foo");
        
        $this->assertNull($tagMap->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMap::update()
     */
    public function testUpdate(): void
    {
        $tagMap = new TagMap();
        
        $this->assertTrue($tagMap->update(false));
        $this->assertTrue($tagMap->update(true));
    }
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMap::setAdapter()
     */
    public function testSetAdapter(): void
    {
        $map = new TagMap();
        
        $this->assertNull($map->setAdapter($this->getMockedAdapter()));
    }
    
    /**
     * @see \Ness\Component\Cache\Tag\TagMap::setNamespace()
     */
    public function testSetNamespace(): void
    {
        $map = new TagMap();
        
        $this->assertNull($map->setNamespace("foo"));
    }
    
}
