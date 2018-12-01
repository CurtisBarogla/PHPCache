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
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Ness\Component\Cache\Serializer\SerializerInterface;
use Ness\Component\Cache\Exception\CacheException;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use Ness\Component\Cache\Exception\SerializerException;

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
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        Cache::unregisterSerializer();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        Cache::unregisterSerializer();
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::get()
     */
    public function testGet(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->exactly(8))->method("unserialize")->withConsecutive(
            ["bar"],
            ["N;"],
            ["b:0;"],
            ["::serializedStdClass::"],
            ["b:io"],
            ["i:7;"],
            ['a'],
            ["::unserializableValue::"]
        )->will($this->onConsecutiveCalls("bar", null, false, new \stdClass(), "b:io", 7, 'a', $this->throwException(new SerializerException())));
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(9))
                ->method("get")
                ->withConsecutive(...$prefixation(["foo", "bar", "moz", "poz", "loz", "noz", "null", "kek", "wow"], Cache::CACHE_FLAG."prefix_"))
                ->will($this->onConsecutiveCalls("bar", "N;", "b:0;", "::serializedStdClass::", "b:io", "i:7;", null, 'a', "::unserializableValue::"));
        });
        
        $cache = new Cache($adapter, null, "prefix");
        
        $this->assertSame("bar", $cache->get("foo"));
        $this->assertNull($cache->get("bar"));
        $this->assertFalse($cache->get("moz"));
        $this->assertEquals(new \stdClass(), $cache->get("poz"));
        $this->assertSame("b:io", $cache->get("loz"));
        $this->assertSame(7, $cache->get("noz"));
        $this->assertSame("default", $cache->get("null", "default"));
        $this->assertSame('a', $cache->get("kek"));
        $this->assertSame("default", $cache->get("wow", "default"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::set()
     */
    public function testSet(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->exactly(4))->method("serialize")->withConsecutive(
            [null],
            [false],
            [new \stdClass()],
            [["::unserializableValue::"]]
        )->will($this->onConsecutiveCalls("N;", "b:0;", "::serializedStdclass::", $this->throwException(new SerializerException())));
        
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $values = [
                [$prefixation("foo", Cache::CACHE_FLAG."global_"), "bar", 7],
                [$prefixation("bar", Cache::CACHE_FLAG."global_"), "N;", 1],
                [$prefixation("moz", Cache::CACHE_FLAG."global_"), "b:0;", null],
                [$prefixation("poz", Cache::CACHE_FLAG."global_"), "::serializedStdclass::", 1],
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
        $this->assertFalse($cache->set("kek", ["::unserializableValue::"]));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::delete()
     */
    public function testDelete(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("delete")->withConsecutive(...$prefixation(["foo", "bar"], Cache::CACHE_FLAG."global_"))->will($this->onConsecutiveCalls(true, false));
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("purge")->withConsecutive([Cache::CACHE_FLAG."global"], [Cache::CACHE_FLAG."foo"]);
        });
        
        $cache = new Cache($adapter);
        
        $this->assertTrue($cache->clear());
        
        $cache = new Cache($adapter, null, "foo");
        
        $this->assertTrue($cache->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $std = new \stdClass();
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer->expects($this->exactly(7))->method("unserialize")->withConsecutive(
            ["bar"],
            ["N;"],
            ["b:0;"],
            ["::serializedStdclass::"],
            ["b:o"],
            ["i:7;"],
            ["::unserializableValue::"]
        )->will($this->onConsecutiveCalls("bar", null, false, $std, "b:o", 7, $this->throwException(new SerializerException())));
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation) use ($std): void {
            $keys = \array_map(function(array $key): string {
                return $key[0];
            }, $prefixation(["foo", "bar", "moz", "poz", "loz", "noz", "null", "error"], Cache::CACHE_FLAG."global_"));
            $adapter->expects($this->once())->method("getMultiple")->with($keys)->will($this->returnValue(
                ["bar", "N;", "b:0;", "::serializedStdclass::", "b:o", "i:7;", null, "::unserializableValue::"]
            ));
        });
        
        $cache = new Cache($adapter);
        
        $expected = [
            "foo"       =>  "bar",
            "bar"       =>  null,
            "moz"       =>  false,
            "poz"       =>  $std,
            "loz"       =>  "b:o",
            "noz"       =>  7,
            "null"      =>  "default",
            "error"     =>  "default"
        ];
        
        $this->assertEquals($expected, $cache->getMultiple(["foo", "bar", "moz", "poz", "loz", "noz", "null", "error"], "default"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $std = new \stdClass();
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(12))
            ->method("serialize")
            ->withConsecutive(
                [false],
                [$std],
                [null],
                [7],
                
                [false],
                [$std],
                [null],
                [7],
                
                [false],
                [$std],
                [null],
                [7]
            )->will($this->onConsecutiveCalls(
                "b:0;", "::serializedStdclass::", "N;", "i:7;",
                "b:0;", "::serializedStdclass::", "N;", "i:7;",
                "b:0;", $this->throwException(new SerializerException()), "N;", "i:7;"
            ));
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive(
                [
                    [
                        $prefixation("foo", Cache::CACHE_FLAG."global_")   =>  ["value" => "bar", "ttl" => null],
                        $prefixation("bar", Cache::CACHE_FLAG."global_")   =>  ["value" => "b:0;", "ttl" => null],
                        $prefixation("moz", Cache::CACHE_FLAG."global_")   =>  ["value" => "::serializedStdclass::", "ttl" => null],
                        $prefixation("poz", Cache::CACHE_FLAG."global_")   =>  ["value" => "N;", "ttl" => null],
                        $prefixation("loz", Cache::CACHE_FLAG."global_")   =>  ["value" => "i:7;", "ttl" => null]
                    ]
                ],
                [
                    [
                        $prefixation("foo", Cache::CACHE_FLAG."global_")   =>  ["value" => "bar", "ttl" => 7],
                        $prefixation("bar", Cache::CACHE_FLAG."global_")   =>  ["value" => "b:0;", "ttl" => 7],
                        $prefixation("moz", Cache::CACHE_FLAG."global_")   =>  ["value" => "::serializedStdclass::", "ttl" => 7],
                        $prefixation("poz", Cache::CACHE_FLAG."global_")   =>  ["value" => "N;", "ttl" => 7],
                        $prefixation("loz", Cache::CACHE_FLAG."global_")   =>  ["value" => "i:7;", "ttl" => 7]
                    ]
                ],
                [
                    [
                        $prefixation("foo", Cache::CACHE_FLAG."global_")   =>  ["value" => "bar", "ttl" => 7],
                        $prefixation("bar", Cache::CACHE_FLAG."global_")   =>  ["value" => "b:0;", "ttl" => 7],
                        $prefixation("poz", Cache::CACHE_FLAG."global_")   =>  ["value" => "N;", "ttl" => 7],
                        $prefixation("loz", Cache::CACHE_FLAG."global_")   =>  ["value" => "i:7;", "ttl" => 7]
                    ]
                ]
                )->will($this->returnValue(null));
        });
        
        $cache = new Cache($adapter);
        
        $values = [
            "foo"   =>  "bar",
            "bar"   =>  false,
            "moz"   =>  $std,
            "poz"   =>  null,
            "loz"   =>  7
        ];
        
        $this->assertTrue($cache->setMultiple($values));
        $this->assertTrue($cache->setMultiple($values, 7));
        $this->assertFalse($cache->setMultiple($values, 7));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $keys = \array_map(function(array $key): string {
                return $key[0];
            }, $prefixation(["foo", "bar"], Cache::CACHE_FLAG."global_"));
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("has")->withConsecutive(...$prefixation(["foo", "bar"], Cache::CACHE_FLAG."global_"))->will($this->onConsecutiveCalls(true, false));
        });
        
        $cache = new Cache($adapter);
        
        $this->assertTrue($cache->has("foo"));
        $this->assertFalse($cache->has("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::registerSerializer()
     */
    public function testRegisterSerializer(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        
        $this->assertNull(Cache::registerSerializer($serializer));
        $this->assertNull(Cache::registerSerializer($serializer));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::unregisterSerializer()
     */
    public function testUnregisterSerializer(): void
    {
        $this->assertNull(Cache::unregisterSerializer());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::__constructi()
     */
    public function testException__constructWhenSerializerIsNotRegistered(): void
    {
        $class = Cache::class;
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage("Serializer is not registered. Did you forget to set it via {$class}::registerSerializer() method ?");
        
        $cache = new Cache($this->getMockBuilder(CacheAdapterInterface::class)->getMock());
    }
    
    // will only test on get... do not want to replicate for all methods
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::get()
     */
    public function testExceptionWhenAnInvalidKeyIsGiven(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
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
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Values/Keys MUST be an array or a Traversable implementation. 'string' given");
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->getMultiple("foo");
    }
    
    /**
     * @see \Ness\Component\Cache\PSR16\Cache::set()
     */
    public function testExceptionWhenInvalidTtlTypeIsGiven(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        Cache::registerSerializer($serializer);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ttl MUST be null or an int (time in seconds) or an instance of DateInterval. 'string' given");
        
        $cache = new Cache($this->getMockedAdapter());
        
        $cache->set("foo", "bar", "foo");
    }
    
}
