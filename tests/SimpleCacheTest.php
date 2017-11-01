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

use Zoe\Component\Cache\SimpleCache;
use Psr\SimpleCache\CacheInterface;
use Zoe\Component\Cache\Exception\SimpleCache\InvalidArgumentException;
use Zoe\Component\Internal\GeneratorTrait;

/**
 * SimpleCache testcase
 *
 * @see \Zoe\Component\Cache\SimpleCache
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class SimpleCacheTest extends CacheTestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache
     */
    public function testInterface(): void
    {
        $adapter = $this->getMockedAdapter();
        $cache = new SimpleCache($adapter);
        
        $this->assertInstanceOf(CacheInterface::class, $cache);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::get()
     */
    public function testGet(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("get")->with("foo")->will($this->returnValue("bar"));
        
        $cache = new SimpleCache($adapter);
        $this->assertSame("bar", $cache->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::get()
     */
    public function testGetWithSerializedValue(): void
    {
        $value = \serialize(7);
        $adapter = $this->getMockedAdapter();
        $adapter->method("get")->with("foo")->will($this->returnValue($value));
        
        $cache = new SimpleCache($adapter);
        $this->assertSame(7, $cache->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::get()
     */
    public function testGetWhenNotInTheStorageAndUseDefault(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("get")->with("foo")->will($this->returnValue(null));
        
        $cache = new SimpleCache($adapter);
        $this->assertSame("bar", $cache->get("foo", "bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::set()
     */
    public function testSet(): void
    {
        // success
        $adapter = $this->getMockedAdapter();
        $adapter->method("set")->with("foo", "bar", null)->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->set("foo", "bar"));
        
        // error
        $adapter = $this->getMockedAdapter();
        $adapter->method("set")->with("foo", "bar", null)->will($this->returnValue(false));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->set("foo", "bar"));
    
        // ttl
        $adapter = $this->getMockedAdapter();
        $adapter->method("set")->with("foo", "bar", 7)->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->set("foo", "bar", 7));
        
        //ttl DateInterval
        $adapter = $this->getMockedAdapter();
        $adapter->method("set")->with("foo", "bar", 7)->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->set("foo", "bar", \DateInterval::createFromDateString("plus 7 seconds")));
        
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::set()
     */
    public function testSetWithSerializedValue(): void
    {
        $value = \serialize(7);
        $adapter = $this->getMockedAdapter();
        $adapter->method("set")->with("foo", $value, null)->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->set("foo", 7));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::delete()
     */
    public function testDelete(): void
    {
        // success
        $adapter = $this->getMockedAdapter();
        $adapter->method("del")->with("foo")->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->delete("foo"));
        
        // error
        $adapter = $this->getMockedAdapter();
        $adapter->method("del")->with("foo")->will($this->returnValue(false));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->delete("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::clear()
     */
    public function testClear(): void
    {
        // success
        $adapter = $this->getMockedAdapter();
        $adapter->method("clear")->with(null)->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->clear());
        
        // error
        $adapter = $this->getMockedAdapter();
        $adapter->method("clear")->with(null)->will($this->returnValue(false));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->clear());
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter
            ->method("getMultiple")
            ->with(["foo", "bar"])
            ->will($this->returnValue($this->getGenerator(["foo" => "bar", "bar" => null])));
        
        $cache = new SimpleCache($adapter);
        $this->assertSame(["foo" => "bar", "bar" => "default"], $cache->getMultiple(["foo", "bar"], "default"));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::getMultiple()
     */
    public function testSetMultiple(): void
    {
        $arg = [
            "foo"   =>  [
                "value"     =>  "bar",
                "ttl"       =>  42
            ],
            "bar"   =>  [
                "value"     =>  \serialize(7),
                "ttl"       =>  42
            ]
        ];
        
        // success
        $adapter = $this->getMockedAdapter();
        $adapter->method("setMultiple")->with($arg)->will($this->returnValue(["foo" => true, "bar" => true]));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->setMultiple(["foo" => "bar", "bar" => 7], \DateInterval::createFromDateString("plus 42 seconds")));
        
        //error
        $adapter = $this->getMockedAdapter();
        $adapter->method("setMultiple")->with($arg)->will($this->returnValue(["foo" => true, "bar" => false]));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->setMultiple(["foo" => "bar", "bar" => 7], \DateInterval::createFromDateString("plus 42 seconds")));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        // success
        $adapter = $this->getMockedAdapter();
        $adapter->method("delMultiple")->with(["foo", "bar"])->will($this->returnValue(["foo" => true, "bar" => true]));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->deleteMultiple(["foo", "bar"]));
        
        // error
        $adapter = $this->getMockedAdapter();
        $adapter->method("delMultiple")->with(["foo", "bar"])->will($this->returnValue(["foo" => false, "bar" => true]));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->deleteMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::has()
     */
    public function testHas(): void
    {
        // exists
        $adapter = $this->getMockedAdapter();
        $adapter->method("exists")->with("foo")->will($this->returnValue(true));
        
        $cache = new SimpleCache($adapter);
        $this->assertTrue($cache->has("foo"));
        
        // does not exist
        $adapter = $this->getMockedAdapter();
        $adapter->method("exists")->with("foo")->will($this->returnValue(false));
        
        $cache = new SimpleCache($adapter);
        $this->assertFalse($cache->has("foo"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::get()
     */
    public function testExceptionGetWhenInvalidKeyIsGiven(): void
    {
        $this->doTestExceptionThrown("get", \str_repeat("f", 65));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::set()
     */
    public function testExceptionSetWhenInvalidKeyIsGiven(): void
    {
        $this->doTestExceptionThrown("set", \str_repeat("f", 65), "bar", null);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::set()
     */
    public function testExceptionSetWhenInvalidTtlIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ttl MUST be null, an int or an instance of DateInterval. 'boolean' given");
        
        $cache = new SimpleCache($this->getMockedAdapter());
        $cache->set("foo", "bar", true);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::delete()
     */
    public function testExceptionDeleteWhenInvalidKeyIsGiven(): void
    {
        $this->doTestExceptionThrown("delete", \str_repeat("f", 65));
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::getMultiple()
     */
    public function testExceptionGetMultipleWhenInvalidKeyIsGiven(): void
    {
        $key = \str_repeat("f", 65);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' cannot exceed 64 characters");
        
        $adapter = $this->getMockedAdapter();
        
        $cache = new SimpleCache($adapter);
        $cache->getMultiple([$key, "foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::getMultiple()
     */
    public function testExceptionWhenANonIterableIsGivenWhenGetMultiple(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys must be an iterable");
        
        $cache = new SimpleCache($this->getMockedAdapter());
        $cache->getMultiple("foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::setMultiple()
     */
    public function testExceptionWhenANonIterableIsGivenWhenSetMultiple(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys must be an iterable");
        
        $cache = new SimpleCache($this->getMockedAdapter());
        $cache->setMultiple("foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::deleteMultiple()
     */
    public function testExceptionWhenANonIterableIsGivenWhenDeleteMultiple(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Keys must be an iterable");
        
        $cache = new SimpleCache($this->getMockedAdapter());
        $cache->deleteMultiple("foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::setMultiple()
     */
    public function testExceptionSetMultipleWhenInvalidKeyIsGiven(): void
    {
        $key = \str_repeat("f", 65);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' cannot exceed 64 characters");
        
        $adapter = $this->getMockedAdapter();
        
        $cache = new SimpleCache($adapter);
        $cache->setMultiple([$key => "foo", "bar" => "foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::setMultiple()
     */
    public function testExceptionSetMultipleWhenInvalidTtlIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ttl MUST be null, an int or an instance of DateInterval. 'boolean' given");
        
        $adapter = $this->getMockedAdapter();
        
        $cache = new SimpleCache($adapter);
        $cache->setMultiple(["foo" => "foo", "bar" => "foo"], true);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::deleteMultiple()
     */
    public function testExceptionDeleteMultipleWhenInvalidKeyIsGiven(): void
    {
        $key = \str_repeat("f", 65);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' cannot exceed 64 characters");
        
        $adapter = $this->getMockedAdapter();
        
        $cache = new SimpleCache($adapter);
        $cache->deleteMultiple([$key, "foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\SimpleCache::has()
     */
    public function testExceptionHasWhenInvalidKeyIsGiven(): void
    {
        $this->doTestExceptionThrown("has", str_repeat("f", 65));
    }
    
    /**
     * Simply test that exception is thrown when invalid key is given
     * 
     * @param string $method
     *   Method to test
     * @param mixed ...$args
     *   Args to pass to the method. Key MUST be the first arg
     */
    private function doTestExceptionThrown(string $method, ...$args): void
    {
        $key = $args[0];
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' cannot exceed 64 characters");
        
        $cache = new SimpleCache($this->getMockedAdapter());
        $cache->{$method}(...$args);
    }
    
}
