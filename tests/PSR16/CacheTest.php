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
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\PSR16\Cache;
use Ness\Component\Cache\PSR16\Exception\InvalidArgumentException;
use NessTest\Component\Cache\Fixtures\InvalidPSR16Cache;

/**
 * Cache testcase
 * 
 * @see \Ness\Component\Cache\PSR16\Cache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTest extends CacheTestCase
{
 
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::get()
     */
    public function testGet(): void
    {
        $std = new \stdClass();
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($std): void {
            $adapter
                ->expects($this->exactly(7))
                ->method("get")
                ->withConsecutive(...$prefixation(["foo", "bar", "moz", "poz", "loz", "noz", "null"], Cache::CACHE_FLAG))
                ->will($this->onConsecutiveCalls("bar", "N;", "b:0;", \serialize($std), "b:io", "i:7;", null));
        });
        
        $cache = new Cache($adapter);
        
        $this->assertSame("bar", $cache->get("foo"));
        $this->assertNull($cache->get("bar"));
        $this->assertFalse($cache->get("moz"));
        $this->assertEquals($std, $cache->get("poz"));
        $this->assertSame("b:io", $cache->get("loz"));
        $this->assertSame(7, $cache->get("noz"));
        $this->assertSame("default", $cache->get("null", "default"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::set()
     */
    public function testSet(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $values = [
                [$prefixation("foo", Cache::CACHE_FLAG), "bar", 7],
                [$prefixation("bar", Cache::CACHE_FLAG), "N;", 1],
                [$prefixation("moz", Cache::CACHE_FLAG), "b:0;", null],
                [$prefixation("poz", Cache::CACHE_FLAG), \serialize(new \stdClass()), 1],
            ];
            $adapter
                ->expects($this->exactly(4))
                ->method("set")
                ->withConsecutive(...$values)
                ->will($this->onConsecutiveCalls(true, true, false, true));
        });
        
        $cache = new Cache($adapter, 7);
        
        $this->assertTrue($cache->set("foo", "bar"));
        $this->assertTrue($cache->set("bar", null, \DateInterval::createFromDateString("plus 1 second")));
        $this->assertFalse($cache->set("moz", false, null));
        $this->assertTrue($cache->set("poz", new \stdClass(), 1));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::delete()
     */
    public function testDelete(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("delete")->withConsecutive(...$prefixation(["foo", "bar"], Cache::CACHE_FLAG))->will($this->onConsecutiveCalls(true, false));
        });
        
        $cache = new Cache($adapter);
        
        $this->assertTrue($cache->delete("foo"));
        $this->assertFalse($cache->delete("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::clear()
     */
    public function testClear(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(Cache::CACHE_FLAG);
        });
        
        $cache = new Cache($adapter);
        
        $this->assertTrue($cache->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $std = new \stdClass();
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($std): void {
            $keys = \array_map(function(array $key): string {
                return $key[0];
            }, $prefixation(["foo", "bar", "moz", "poz", "loz", "noz", "null"], Cache::CACHE_FLAG));
            $adapter->expects($this->once())->method("getMultiple")->with($keys)->will($this->returnValue(
                ["bar", "N;", "b:0;", \serialize($std), "b:o", "i:7;", null]
            ));
        });
        
        $cache = new Cache($adapter);
        
        $expected = [
            "foo"   =>  "bar",
            "bar"   =>  null,
            "moz"   =>  false,
            "poz"   =>  $std,
            "loz"   =>  "b:o",
            "noz"   =>  7,
            "null"  =>  "default"
        ];
        
        $this->assertEquals($expected, $cache->getMultiple(["foo", "bar", "moz", "poz", "loz", "noz", "null"], "default"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("setMultiple")
                ->withConsecutive(
                [
                    [
                        $prefixation("foo", Cache::CACHE_FLAG)   =>  ["value" => "bar", "ttl" => null],
                        $prefixation("bar", Cache::CACHE_FLAG)   =>  ["value" => "b:0;", "ttl" => null],
                        $prefixation("moz", Cache::CACHE_FLAG)   =>  ["value" => \serialize(new \stdClass()), "ttl" => null],
                        $prefixation("poz", Cache::CACHE_FLAG)   =>  ["value" => "N;", "ttl" => null],
                        $prefixation("loz", Cache::CACHE_FLAG)   =>  ["value" => "i:7;", "ttl" => null]
                    ]
                ],
                [
                    [
                        $prefixation("foo", Cache::CACHE_FLAG)   =>  ["value" => "bar", "ttl" => 7],
                        $prefixation("bar", Cache::CACHE_FLAG)   =>  ["value" => "b:0;", "ttl" => 7],
                        $prefixation("moz", Cache::CACHE_FLAG)   =>  ["value" => \serialize(new \stdClass()), "ttl" => 7],
                        $prefixation("poz", Cache::CACHE_FLAG)   =>  ["value" => "N;", "ttl" => 7],
                        $prefixation("loz", Cache::CACHE_FLAG)   =>  ["value" => "i:7;", "ttl" => 7]
                    ]
                ])->will($this->returnValue(null));
        });
        
        $cache = new Cache($adapter);
        
        $values = [
            "foo"   =>  "bar",
            "bar"   =>  false,
            "moz"   =>  new \stdClass(),
            "poz"   =>  null,
            "loz"   =>  7
        ];
        
        $this->assertTrue($cache->setMultiple($values));
        $this->assertTrue($cache->setMultiple($values, 7));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $keys = \array_map(function(array $key): string {
                return $key[0];
            }, $prefixation(["foo", "bar"], Cache::CACHE_FLAG));
            $adapter->expects($this->exactly(2))->method("deleteMultiple")->withConsecutive([$keys])->will($this->onConsecutiveCalls(null, ["foo"]));
        });
        
        $cache = new Cache($adapter);
        
        $iterable = new class implements \IteratorAggregate {
            private $keys = ["foo", "bar"];
            public function getIterator()
            {
                foreach ($this->keys as $key)
                    yield $key;
            }
        };
        
        $this->assertTrue($cache->deleteMultiple($iterable));
        $this->assertFalse($cache->deleteMultiple($iterable));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::has()
     */
    public function testHas(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("has")->withConsecutive(...$prefixation(["foo", "bar"], Cache::CACHE_FLAG))->will($this->onConsecutiveCalls(true, false));
        });
        
        $cache = new Cache($adapter);
        
        $this->assertTrue($cache->has("foo"));
        $this->assertFalse($cache->has("bar"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    // will only test on get... do not want to replicate for all methods
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::get()
     */
    public function testExceptionWhenAnInvalidKeyIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This cache key 'fooé' is invalid. It contains invalid characters. Characters allowed : " . Cache::ACCEPTED_CHARACTERS);
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->get("fooé");
    }
    
    /**
    * @see \Ness\Component\Cache\PSR16\Cache::get()
    */
    public function testExceptionWhenAKeyWithReservedCharactersIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This cache key 'foo@' is invalid. It contains reserved characters '@' from list " . Cache::RESERVED_CHARACTERS);
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->get("foo@");
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::get()
     */
    public function testExceptionWhenKeyIsTooLong(): void
    {
        $key = \str_repeat("foo", 64);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This cache key '{$key}' is invalid. Max characters allowed " . Cache::MAX_LENGTH);
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->get($key);
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::getMultiple()
     */
    public function testExceptionWhenKeysGivenAreNotTraversableOrAnArray(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Values/Keys MUST be an array or a Traversable implementation. 'string' given");
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->getMultiple("foo");
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache
     */
    public function testExceptionWhenANonDeclaredRequiredConstIsFound(): void
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessage("A required constant has been not defined into the implementation of the cache component. Undefined class constant 'MAX_LENGTH'");
        
        $cache = new InvalidPSR16Cache();
        $cache->exec("foo");
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::set()
     */
    public function testExceptionWhenInvalidTtlTypeIsGiven(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Ttl MUST be null or an int (time in seconds) or an instance of DateInterval. 'string' given");
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->set("foo", "bar", "foo");
    }
    
}
