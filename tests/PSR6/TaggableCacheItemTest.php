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
use Ness\Component\Cache\Serializer\SerializerInterface;

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
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        TaggableCacheItem::$serializer = null;
    }
    
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
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::createFromJson()
     */
    public function testCreateFromJson(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->once())->method("unserialize")->with("Foo")->will($this->returnValue("Foo"));
        
        TaggableCacheItem::$serializer = $serializer;

        $serialized = "{\"value\":\"Foo\",\"ttl\":-1,\"saved\":[\"foo\",\"bar\"]}";
        
        $item = TaggableCacheItem::createFromJson("Foo", $serialized);
        
        $this->assertSame("Foo", $item->getKey());
        $this->assertSame(CacheItem::DEFAULT_TTL, $item->getTtl());
        $this->assertSame(["foo", "bar"], $item->getCurrent());
        $this->assertSame(["foo", "bar"], $item->getPreviousTags());
        $this->assertTrue($item->isHit());
        $this->assertSame("Foo", $item->get());
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
     * @see \Ness\Component\Cache\PSR6\TaggableCacheItem::jsonSerialize()
     */
    public function testJsonSerialize(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->once())->method("serialize")->with(null)->will($this->returnValue("::serializeNull::"));

        TaggableCacheItem::$serializer = $serializer;
        
        $item = new TaggableCacheItem("foo");
        
        $serialized = \json_encode($item);
        
        $this->assertSame("{\"value\":\"::serializeNull::\",\"ttl\":-1,\"saved\":[]}", $serialized);
        
        $item = new TaggableCacheItem("foo");
        $item->set("Foo");
        $item->setTags(["foo", "bar"]);
        
        $serialized = \json_encode($item);
        
        $this->assertSame("{\"value\":\"Foo\",\"ttl\":-1,\"saved\":[\"foo\",\"bar\"]}", $serialized);
    }
    
}
