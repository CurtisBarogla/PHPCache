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
use Ness\Component\Cache\PSR6\CacheItem;
use Ness\Component\Cache\PSR6\Exception\InvalidArgumentException;

/**
 * CacheItem testcase
 * 
 * @see \Ness\Component\Cache\PSR6\CacheItem
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::getKey()
     */
    public function testGetKey(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertSame("Foo", $item->getKey());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::get()
     */
    public function testGet(): void
    {
        $item = new CacheItem("Foo");
        
        $item->set("Foo");
        
        $this->assertSame("Foo", $item->get());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::isHit()
     */
    public function testIsHit(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertFalse($item->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::set()
     */
    public function testSet(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertSame($item, $item->set("Foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::expiresAt()
     */
    public function testExpiresAt(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertSame($item, $item->expiresAt(null));
        $this->assertSame($item, $item->expiresAt(new \DateTime()));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::expireAfter()
     */
    public function testExpiresAfter(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertSame($item, $item->expiresAfter(null));
        $this->assertSame($item, $item->expiresAfter(42));
        $this->assertSame($item, $item->expiresAfter(new \DateInterval("P1D")));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::getTtl()
     */
    public function testGetTtl(): void
    {
        $item = new CacheItem("Foo");
        
        $this->assertInfinite($item->getTtl());
        
        $item->expiresAfter(null);
        $this->assertNull($item->getTtl());
        
        $item->expiresAfter(42);
        $this->assertSame(42, $item->getTtl());
        
        $item->expiresAfter(new \DateInterval("P1D"));
        $this->assertSame(86400, $item->getTtl());
        
        $item->expiresAt(new \DateTime("NOW + 1 day"));
        $this->assertSame(86400, $item->getTtl());
        
        $item->expiresAt(null);
        $this->assertNull($item->getTtl());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::serialize()
     */
    public function testSerialize(): void
    {
        $item = new CacheItem("Foo");
        $item->set("Foo")->expiresAfter(42);
        
        $this->assertNotFalse(\serialize($item));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::unserialize()
     */
    public function testUnserialize(): void
    {
        $item = new CacheItem("Foo");
        $item->set("Foo")->expiresAfter(42);
        
        $item = \unserialize(\serialize($item));
        
        $this->assertInstanceOf(CacheItem::class, $item);
        $this->assertSame("Foo", $item->getKey());
        $this->assertSame("Foo", $item->get());
        $this->assertTrue($item->isHit());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::expireAt()
     */
    public function testExceptionExpiresAtWhenAnInvalidTypeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expiration time on 'Foo' cache item MUST be null or an instance of DateTimeInterface. 'string' given");
        
        $item = new CacheItem("Foo");
        
        $item->expiresAt("Foo");
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItem::expireAfter()
     */
    public function testExceptionExpiresAfterWhenAnInvalidTypeIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Expiration time on 'Foo' cache item MUST be null an int (representing time in seconds) or an instance of DateInterval. 'string' given");
        
        $item = new CacheItem("Foo");
        
        $item->expiresAfter("Foo");
    }
    
}
