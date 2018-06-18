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
use Ness\Component\Cache\PSR6\TaggableCacheItem;
use Ness\Component\Cache\PSR6\CacheItem;
use Cache\TagInterop\TaggableCacheItemInterface;

/**
 * TaggableCacheItem testcase
 * 
 * @see \Ness\Component\Cache\PSR6\TaggableCacheItem
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class TaggableCacheItemTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::setTags()
     */
    public function testSetTags(): void
    {
        $item = new TaggableCacheItem("foo");
        
        $this->assertSame($item, $item->setTags(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::getPreviousTags()
     */
    public function testGetPreviousTags(): void
    {
        $item = new TaggableCacheItem("foo");
        
        $this->assertEmpty($item->getPreviousTags());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::getCurrent()
     */
    public function testGetCurrent(): void
    {
        $item = new TaggableCacheItem("foo");
        
        $this->assertSame([], $item->getCurrent());
        
        $item->setTags(["foo", "bar"]);
        
        $this->assertSame(["foo", "bar"], $item->getCurrent());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::convert()
     */
    public function testConvert(): void
    {
        $item = $this->getMockBuilder(CacheItem::class)->disableOriginalConstructor()->getMock();
        $item->expects($this->once())->method("getKey")->will($this->returnValue("foo"));
        $item->expects($this->once())->method("get")->will($this->returnValue("bar"));
        $item->expects($this->once())->method("getTtl")->will($this->returnValue(7));
        $item->expects($this->once())->method("isHit")->will($this->returnValue(true));
        
        $item = TaggableCacheItem::convert($item);
        
        $this->assertSame("foo", $item->getKey());
        $this->assertSame("bar", $item->get());
        $this->assertSame(7, $item->getTtl());
        $this->assertTrue($item->isHit());
        
        $item = $this->getMockBuilder(TaggableCacheItemInterface::class)->getMock();
        
        $this->assertSame($item, TaggableCacheItem::convert($item));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::serialize()
     */
    public function testSerialize(): void
    {
        $item = new TaggableCacheItem("foo");
        
        $this->assertNotFalse(\serialize($item));
        
        $item = new TaggableCacheItem("foo");
        
        $item->setTags(["foo", "bar"]);
        
        $this->assertNotFalse(\serialize($item));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::unserialize()
     */
    public function testUnserialize(): void
    {
        $item = new TaggableCacheItem("foo");
        
        $serialized = \serialize($item);
        $unserialized = \unserialize($serialized);

        $this->assertSame([], $unserialized->getPreviousTags());
        
        $unserialized->setTags(["foo", "bar"]);
        
        $this->assertSame([], $unserialized->getPreviousTags());
        $this->assertSame(["foo", "bar"], $unserialized->getCurrent());
        
        $serialized = \serialize($unserialized);
        $unserialized = \unserialize($serialized);
        
        $this->assertSame(["foo", "bar"], $unserialized->getPreviousTags());
        $this->assertSame(["foo", "bar"], $unserialized->getCurrent());
        
        $unserialized->setTags(["moz", "poz"]);
        
        $this->assertSame(["foo", "bar"], $unserialized->getPreviousTags());
        $this->assertSame(["moz", "poz"], $unserialized->getCurrent());
    }
    
}
