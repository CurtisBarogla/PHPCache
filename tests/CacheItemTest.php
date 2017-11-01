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

namespace ZoeTest\Component\Cache;

use Zoe\Component\Cache\CacheItem;
use Psr\Cache\CacheItemInterface;
use Zoe\Component\Cache\Exception\CachePool\InvalidArgumentException;

/**
 * CacheItem testcase
 * 
 * @see \Zoe\Component\Cache\CacheItem
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemTest extends CacheTestCase
{
    
    /**
     * @see \Zoe\Component\Cache\CacheItem
     */
    public function testInterface(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInstanceOf(CacheItemInterface::class, $item);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::getKey()
     */
    public function testGetKey(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertSame("foo", $item->getKey());
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::get()
     */
    public function testGet(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertNull($item->get());
        
        //string
        $item->set("foo");
        $this->assertSame("foo", $item->get());
        
        //int
        $item->set(42);
        $this->assertSame(42, $item->get());
        
        // float
        $item->set(42.42);
        $this->assertSame(42.42, $item->get());
            
        // boolean
        $item->set(true);
        $this->assertSame(true, $item->get());
        
        $item->set(false);
        $this->assertSame(false, $item->get());
        
        // object
        $fixture = new \stdClass();
        $fixture->foo = "foo";

        $item->set($fixture);
        $this->assertInstanceOf(\stdClass::class, $item->get());
        $this->assertSame("foo", $item->get()->foo);
        
        // array
        $fixture = ["foo" => "bar", "bar" => "foo"];
        
        $item->set($fixture);
        $this->assertSame(["foo" => "bar", "bar" => "foo"], $item->get());
        
        // null
        $item->set(null);
        $this->assertSame(null, $item->get());
        
        // kinda serialized value
        $item->set("v:");
        $this->assertSame("v:", $item->get());
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::isHit()
     */
    public function testIsHit(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertFalse($item->isHit());
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::set()
     */
    public function testSet(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInstanceOf(CacheItemInterface::class, $item->set("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAt()
     */
    public function testExpiresAt(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInstanceOf(
            CacheItemInterface::class, 
            $item->expiresAt((new \DateTime("NOW + 50 seconds"))));
        $this->assertInstanceOf(CacheItemInterface::class, $item->expiresAt(null));
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAfter()
     */
    public function testExpiresAfter(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInstanceOf(CacheItemInterface::class, $item->expiresAfter(42));
        $this->assertInstanceOf(CacheItemInterface::class, $item->expiresAfter(\DateInterval::createFromDateString("plus 1 day")));
        $this->assertInstanceOf(CacheItemInterface::class, $item->expiresAfter(null));
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::getExpiration()
     */
    public function testGetExpirationExpiresAt(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInfinite($item->getExpiration());
        
        $expected = (new \DateTime("NOW + 42 seconds"))->format(GlobalConfiguration::DATE_FORMAT_TEST);
        $item->expiresAt(new \DateTime("NOW + 42 seconds"));
        $this->assertSame($expected, $item->getExpiration()->format(GlobalConfiguration::DATE_FORMAT_TEST));
        
        $expected = null;
        $item->expiresAt(null);
        $this->assertNull($item->getExpiration());
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::getExpiration()
     */
    public function testGetExpirationExpiresAfter(): void
    {
        $item = new CacheItem("foo");
        
        $this->assertInfinite($item->getExpiration());
        
        // DateInterval
        $expected = (new \DateTime("NOW + 1 day"))->format(GlobalConfiguration::DATE_FORMAT_TEST);
        $item->expiresAfter(\DateInterval::createFromDateString("plus 1 day"));
        $this->assertSame($expected, $item->getExpiration()->format(GlobalConfiguration::DATE_FORMAT_TEST));
        
        // int (time in seconds)
        $expected = (new \DateTime("NOW + 42 seconds"))->format(GlobalConfiguration::DATE_FORMAT_TEST);
        $item->expiresAfter(42);
        $this->assertSame($expected, $item->getExpiration()->format(GlobalConfiguration::DATE_FORMAT_TEST));
        
        //null
        $expected = null;
        $item->expiresAfter(null);
        $this->assertNull($item->getExpiration());
    }
    
    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::set()
     */
    public function testExceptionWhenAResouceIsGivenAsACacheValue(): void
    {
        $message = "Impossible to serialize resource value";
        
        $resource = \fopen(__DIR__."/Fixtures/CacheItem/foo.txt", "r");
        
        $this->doTestExceptionOnInvalidArg($message, "set", $resource);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::set()
     */
    public function testExceptionWhenAnAnonymousClassIsGiven(): void
    {
        $message = "Cannot serialize anonymous 'object'";
        
        $anonymous = new class {
            
        };
        
        $this->doTestExceptionOnInvalidArg($message, "set", $anonymous);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::set()
     */
    public function testExceptionWhenAClosureIsGiven(): void
    {
        $message = "Cannot serialize anonymous 'function'";
        
        $anonymous = function() {
            
        };
        
        $this->doTestExceptionOnInvalidArg($message, "set", $anonymous);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAt()
     */
    public function testExceptionWhenAnInvalidTypeIsGivenAsExpirationDate(): void
    {
        $message = "Expiration date MUST be an instance of DateTimeInterface or null. 'integer' given";
        
        $this->doTestExceptionOnInvalidArg($message, "expiresAt", 7);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAt()
     */
    public function testExceptionWhenAnInvalidObjectIsGivenAsExpirationDate(): void
    {
        $message = "Expiration date MUST be an instance of DateTimeInterface or null. 'stdClass' given";
        
        $this->doTestExceptionOnInvalidArg($message, "expiresAt", new \stdClass());
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAfter()
     */
    public function testExceptionWhenAnInvalidTypeIsGivenAsExpirationTime(): void
    {
        $message = "Expiration time MUST be an instance of DateInterval, an int or null. 'boolean' given";
        
        $this->doTestExceptionOnInvalidArg($message, "expiresAfter", true);
    }
    
    /**
     * @see \Zoe\Component\Cache\CacheItem::expiresAfter()
     */
    public function testExceptionWhenAnInvalidObjectIsGivenAsExpirationTime(): void
    {
        $message = "Expiration time MUST be an instance of DateInterval, an int or null. 'stdClass' given";
        
        $this->doTestExceptionOnInvalidArg($message, "expiresAfter" ,new \stdClass());
    }
    
    /**
     * Execute exception thrown on invalid arg
     * 
     * @param string $message
     *   Exception message displayed
     * @param string $method
     *   Method tested
     * @param mixed $args
     *   Args given to the method
     */
    private function doTestExceptionOnInvalidArg(string $message, string $method, ...$args): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($message);
        
        $item = new CacheItem("foo");
        $item->{$method}(...$args);
    }
    
}
