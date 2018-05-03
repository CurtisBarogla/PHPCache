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

namespace ZoeTest\Component\Cache\Item;

use ZoeTest\Component\Cache\AbstractCacheTestCase;
use Zoe\Component\Cache\Item\CacheItem;
use Zoe\Component\Cache\Exception\PSR6\InvalidArgumentException;

/**
 * CacheItem testcase
 * 
 * @see \Zoe\Component\Cache\Item\CacheItem
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemTest extends AbstractCacheTestCase
{
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::getKey()
     */
    public function testGetKey(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertSame("foo", $item->getKey());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::get()
     */
    public function testGet(): void
    {
        $item = new CacheItem("foo");
        $item->set("foo");
        
        $this->assertSame("foo", $item->get());
        
        $item = new CacheItem("bar");
        $item->set(["foo"]);
        $this->assertSame(["foo"], $item->get());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::isHit()
     */
    public function testIsHit(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertFalse($item->isHit());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::set()
     */
    public function testSet(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertSame($item, $item->set("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::expiresAt()
     */
    public function testExpiresAt(): void
    {
        $item = new CacheItem("foo");
        $this->assertInfinite($item->getTtl());
        
        $item = new CacheItem("foo");
        
        $time = \time() + 1;
        $this->assertSame($item, $item->expiresAt(\DateTime::createFromFormat("U", (string) $time)));
        $this->assertSame(1, $item->getTtl());
        
        $item = new CacheItem("foo");
        
        $this->assertSame($item, $item->expiresAt(null));
        $this->assertNull($item->getTtl());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::expiresAfter()
     */
    public function testExpiresAfter(): void
    {   
        $item = new CacheItem("foo");
        $this->assertInfinite($item->getTtl());
        
        $item = new CacheItem("foo");

        $this->assertSame($item, $item->expiresAfter(1));
        $this->assertSame(1, $item->getTtl());
        
        $item = new CacheItem("foo");
        
        $this->assertSame($item, $item->expiresAfter(null));
        $this->assertNull($item->getTtl());
        
        $item = new CacheItem("foo");
        
        $this->assertSame($item, $item->expiresAfter(\DateInterval::createFromDateString("plus 1 second")));
        $this->assertSame(1, $item->getTtl());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::serialize()
     * @see \Zoe\Component\Cache\Item\CacheItem::unserialize()
     */
    public function testSerializeUnserialize(): void
    {
        $item = new CacheItem("foo");
        $item->set("foo")->expiresAfter(10);
        
        $serialized = \serialize($item);
        $this->assertNotFalse($serialized);
        $this->assertEquals($item, \unserialize($serialized));
        
        $item = new CacheItem("foo");
        $item->set(["foo"])->expiresAfter(\DateInterval::createFromDateString("plus 1 second"));
        
        $serialized = \serialize($item);
        $this->assertNotFalse($serialized);
        $unserialized = \unserialize($serialized);
        $this->assertSame(["foo"], $unserialized->get());
        $this->assertSame("foo", $unserialized->getKey());
        $this->assertSame(1, $unserialized->getTtl());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::getTtl()
     */
    public function testGetTtl(): void
    {
        $item = new CacheItem("foo");
        $this->assertInfinite($item->getTtl());
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::setHit()
     */
    public function testSetHit(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertFalse($item->isHit());
        $this->assertNull($item->setHit());
        $this->assertTrue($item->isHit());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::expiresAt()
     */
    public function testExceptionExpiresAtWhenAnInvalidExpirationTimeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expiration MUST be an instance of DateTimeInterface or null. 'boolean' given");
        
        $item = new CacheItem("foo");
        $item->expiresAt(true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::expiresAfter()
     */
    public function testExceptionExpiresAfterWhenAnInvalidExpirationTimeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expiration MUST be an instance of DateTimeInterval an int (seconds) or null. 'boolean' given");
        
        $item = new CacheItem("foo");
        $item->expiresAfter(true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Item\CacheItem::serialize()
     */
    public function testExceptionSerializeWhenAnInvalidValueIsSettedIntoItem(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Given value is not handled by this Cache implementation. See message : Serialization of 'class@anonymous' is not allowed");
        
        $item = new CacheItem("foo");
        $item->set(new class() {});
        
        \serialize($item);
    }
    
}
