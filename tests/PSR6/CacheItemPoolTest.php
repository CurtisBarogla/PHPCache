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
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\PSR6\CacheItemPool;
use Ness\Component\Cache\PSR6\CacheItem;
use Ness\Component\Cache\Serializer\SerializerInterface;
use Ness\Component\Cache\Exception\InvalidArgumentException;
use Ness\Component\Cache\Exception\SerializerException;
use Ness\Component\Cache\Exception\CacheException;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;

/**
 * CachePool testcase
 * 
 * @see \Ness\Component\Cache\PSR6\CachePool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheItemPoolTest extends CacheTestCase
{
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        CacheItemPool::unregisterSerializer();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        CacheItemPool::unregisterSerializer();
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::getItem()
     */
    public function testGetItem(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(2))
            ->method("unserialize")
            ->withConsecutive(["bar"], ["::corrupted::"])
            ->will($this->onConsecutiveCalls("bar", $this->throwException(new SerializerException())));
        
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(4))
                ->method("get")->withConsecutive(...$prefixation(["foo", "bar", "moz", "poz"], CacheItemPool::CACHE_FLAG."prefix_"))
                ->will($this->onConsecutiveCalls(
                    '{"value":"bar","ttl":42}', 
                    null, 
                    null,
                    '{"value":"::corrupted::","ttl":42}'));
        });
        
        $pool = new CacheItemPool($adapter, null, "prefix");
        
        $hitted = $pool->getItem("foo");
        $notHitted = $pool->getItem("bar");
        
        $this->assertSame("foo", $hitted->getKey());
        $this->assertSame("bar", $hitted->get());
        $this->assertTrue($hitted->isHit());
        
        $this->assertSame("bar", $notHitted->getKey());
        $this->assertSame(null, $notHitted->get());
        $this->assertFalse($notHitted->isHit());
        
        $item = new CacheItem("moz");
        $item->set("bar");
        
        $pool->saveDeferred($item);
        $this->assertSame($item, $pool->getItem("moz"));
        
        $corrupted = $pool->getItem("poz");
        $this->assertSame("poz", $corrupted->getKey());
        $this->assertNull($corrupted->get());
        $this->assertFalse($corrupted->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::getItems()
     */
    public function testGetItems(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(2))
            ->method("unserialize")
            ->withConsecutive(["bar"], ["::corrupted::"])
            ->will($this->onConsecutiveCalls("bar", $this->throwException(new SerializerException())));
        
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->once())
                ->method("getMultiple")
                ->with(
                    [
                        $prefixation("foo", CacheItemPool::CACHE_FLAG."global_"), 
                        $prefixation("bar", CacheItemPool::CACHE_FLAG."global_"), 
                        $prefixation("moz", CacheItemPool::CACHE_FLAG."global_"),
                        $prefixation("poz", CacheItemPool::CACHE_FLAG."global_")
                    ])
                ->will($this->returnValue(
                    [
                        '{"value":"bar","ttl":42}', 
                        null, 
                        null,
                        '{"value":"::corrupted::","ttl":42}'
                    ]));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertEmpty($pool->getItems());
        
        $moz = new CacheItem("moz");
        $moz->set("bar");
        
        $pool->saveDeferred($moz);
        
        $items = $pool->getItems(["foo", "bar", "moz", "poz"]);
        $foo = $items["foo"];
        $bar = $items["bar"];
        $poz = $items["poz"];
        
        $this->assertSame("foo", $foo->getKey());
        $this->assertSame("bar", $foo->get());
        $this->assertTrue($foo->isHit());
        
        $this->assertSame("bar", $bar->getKey());
        $this->assertNull($bar->get());
        $this->assertFalse($bar->isHit());
        
        $this->assertSame($moz, $items["moz"]);
        
        $this->assertSame("poz", $poz->getKey());
        $this->assertNull($poz->get());
        $this->assertFalse($poz->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::hasItem()
     */
    public function testHasItem(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("has")
                ->withConsecutive(...$prefixation(["foo", "bar"], CacheItemPool::CACHE_FLAG."global_"))
                ->will($this->onConsecutiveCalls(true, false));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->hasItem("foo"));
        $this->assertFalse($pool->hasItem("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::clear()
     */
    public function testClear(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("purge")->withConsecutive([CacheItemPool::CACHE_FLAG."global"], [CacheItemPool::CACHE_FLAG."foo"]);
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->clear());
        
        $pool = new CacheItemPool($adapter, null, "foo");
        
        $this->assertTrue($pool->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::deleteItem()
     */
    public function testDeleteItem(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("delete")
                ->withConsecutive(...$prefixation(["foo", "bar"], CacheItemPool::CACHE_FLAG."global_"))
                ->will($this->onConsecutiveCalls(true, false));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->deleteItem("foo"));
        $this->assertFalse($pool->deleteItem("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::deleteItems()
     */
    public function testDeleteItems(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("deleteMultiple")
                ->withConsecutive([[$prefixation("foo", CacheItemPool::CACHE_FLAG."global_"), $prefixation("bar", CacheItemPool::CACHE_FLAG."global_")]])
                ->will($this->onConsecutiveCalls(null, ["foo"]));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->deleteItems(["foo", "bar"]));
        $this->assertFalse($pool->deleteItems(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::save()
     */
    public function testSave(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->once())
            ->method("serialize")
            ->with(["::nonSerializableValue::"])
            ->will($this->throwException(new SerializerException()));
        
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(6))->method("set")->withConsecutive(
                [
                    $prefixation("bar", CacheItemPool::CACHE_FLAG."global_"), 
                    '{"value":"foo","ttl":null}', 
                    null 
                ],
                [
                    $prefixation("moz", CacheItemPool::CACHE_FLAG."global_"), 
                    '{"value":"poz","ttl":3}', 
                    3
                ],
                // default ttl CachePool setted to null
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG."global_"), 
                    '{"value":"bar","ttl":-1}', 
                    null
                ],
                // default ttl CachePool setted to 7
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG."global_"),
                    '{"value":"bar","ttl":-1}',
                    7
                ],
                // default ttl CachePool setted to a DateInterval
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG."global_"),
                    '{"value":"bar","ttl":-1}',
                    7
                ],
                // default ttl CachePool setted to a Datetime
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG."global_"),
                    '{"value":"bar","ttl":-1}',
                    7
                ]
            )
            ->will($this->onConsecutiveCalls(true, true, true, true, true, true));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->save((new CacheItem("bar"))->set("foo")->expiresAt(null)));
        $this->assertTrue($pool->save((new CacheItem("moz"))->set("poz")->expiresAfter(3)));
        $this->assertTrue($pool->save((new CacheItem("foo"))->set("bar")));
        
        $pool = new CacheItemPool($adapter, 7);
        $this->assertTrue($pool->save((new CacheItem("foo"))->set("bar")));
        
        $pool = new CacheItemPool($adapter, \DateInterval::createFromDateString("plus 7 seconds"));
        $this->assertTrue($pool->save((new CacheItem("foo"))->set("bar")));
        
        $pool = new CacheItemPool($adapter, new \DateTime("NOW + 7 seconds"));
        $this->assertTrue($pool->save((new CacheItem("foo"))->set("bar")));
        
        $this->assertFalse($pool->save((new CacheItem("foo"))->set(["::nonSerializableValue::"])));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::saveDeferred()
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::__destruct()
     */
    public function testSaveDeferred(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("setMultiple");
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->saveDeferred(new CacheItem("foo")));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::commit()
     */
    public function testCommit(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        $serializer
            ->expects($this->exactly(4))
            ->method("serialize")
            ->withConsecutive(
                [["::nonSerializableValue::"]], 
                [["::nonSerializableValue::"]], 
                [["::nonSerializableValue::"]], 
                [["::nonSerializableValue::"]])
            ->will($this->onConsecutiveCalls(
                "::serializedValue::", 
                "::serializedValue::", 
                $this->throwException(new SerializerException()), 
                // call to __destruct()
                "::serializedValue::"));
        
        CacheItemPool::registerSerializer($serializer);
        
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $foo = '{"value":"bar","ttl":-1}';
            $bar = '{"value":"foo","ttl":null}';
            $moz = '{"value":"poz","ttl":3}';
            $poz = '{"value":"::serializedValue::","ttl":3}';
            $adapter
                ->expects($this->exactly(4))
                ->method("setMultiple")
                ->withConsecutive(
                    [
                        [
                            $prefixation("foo", CacheItemPool::CACHE_FLAG."global_") => ["value" => $foo, "ttl" => null],
                            $prefixation("bar", CacheItemPool::CACHE_FLAG."global_") => ["value" => $bar, "ttl" => null],
                            $prefixation("moz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $moz, "ttl" => 3],
                            $prefixation("poz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $poz, "ttl" => 3],
                        ]
                    ],
                    [
                        [
                            $prefixation("foo", CacheItemPool::CACHE_FLAG."global_") => ["value" => $foo, "ttl" => null],
                            $prefixation("bar", CacheItemPool::CACHE_FLAG."global_") => ["value" => $bar, "ttl" => null],
                            $prefixation("moz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $moz, "ttl" => 3],
                            $prefixation("poz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $poz, "ttl" => 3],
                        ]
                    ],
                    [
                        [
                            $prefixation("foo", CacheItemPool::CACHE_FLAG."global_") => ["value" => $foo, "ttl" => null],
                            $prefixation("bar", CacheItemPool::CACHE_FLAG."global_") => ["value" => $bar, "ttl" => null],
                            $prefixation("moz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $moz, "ttl" => 3]
                        ]
                    ],
                    // call to __destruct()
                    [
                        [
                            $prefixation("poz", CacheItemPool::CACHE_FLAG."global_") => ["value" => $poz, "ttl" => 3],
                        ]
                    ]
                )
                ->will($this->onConsecutiveCalls(null, ["foo"], null, null));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $pool->saveDeferred((new CacheItem("foo"))->set("bar"));
        $pool->saveDeferred((new CacheItem("bar"))->set("foo")->expiresAfter(null));
        $pool->saveDeferred((new CacheItem("moz"))->set("poz")->expiresAfter(3));
        $pool->saveDeferred((new CacheItem("poz"))->set(["::nonSerializableValue::"])->expiresAfter(3));
        
        $this->assertTrue($pool->commit());
        $this->assertTrue($pool->commit());
        
        $pool->saveDeferred((new CacheItem("foo"))->set("bar"));
        $pool->saveDeferred((new CacheItem("bar"))->set("foo")->expiresAfter(null));
        $pool->saveDeferred((new CacheItem("moz"))->set("poz")->expiresAfter(3));
        $pool->saveDeferred((new CacheItem("poz"))->set(["::nonSerializableValue::"])->expiresAfter(3));
        
        $this->assertFalse($pool->commit());
        
        $pool->saveDeferred((new CacheItem("foo"))->set("bar"));
        $pool->saveDeferred((new CacheItem("bar"))->set("foo")->expiresAfter(null));
        $pool->saveDeferred((new CacheItem("moz"))->set("poz")->expiresAfter(3));
        $pool->saveDeferred((new CacheItem("poz"))->set(["::nonSerializableValue::"])->expiresAfter(3));
        
        $this->assertFalse($pool->commit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::registerSerializer()
     */
    public function testRegisterSerializer(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        
        $this->assertNull(CacheItemPool::registerSerializer($serializer));
        $this->assertNull(CacheItemPool::registerSerializer($serializer));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::unregisterSerializer()
     */
    public function testUnregisterSerializer(): void
    {
        $this->assertNull(CacheItemPool::unregisterSerializer());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::__construct()
     */
    public function testException__constructWhenSerializerNotRegistered(): void
    {
        $class = CacheItemPool::class;
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage("Serializer is not registered. Did you forget to set it via {$class}::registerSerializer() method ?");
        
        $pool = new CacheItemPool($this->getMockBuilder(CacheAdapterInterface::class)->getMock());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::__construct()
     */
    public function testException__constructWhenDefaultTtlIsNotAValidType(): void
    {
        $serializer = $this->getMockBuilder(SerializerInterface::class)->getMock();
        CacheItemPool::registerSerializer($serializer);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Default ttl for CachePool MUST be null, an int (time in seconds), an implementation of DateTimeInterface or a DateInterval. 'string' given");
        
        $pool = new CacheItemPool($this->getMockedAdapter(), "foo");
    }
    
}
