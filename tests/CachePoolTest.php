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

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Zoe\Component\Cache\CacheItem;
use Zoe\Component\Cache\CachePool;
use Zoe\Component\Cache\Adapter\AdapterInterface;
use Zoe\Component\Cache\Exception\CachePool\InvalidArgumentException;

/**
 * CachePool testcase
 * 
 * @see \Zoe\Component\Cache\CachePool
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CachePoolTest extends CacheTestCase
{
    
    /**
     * @see \Zoe\Component\Cache\CachePool::setDefaultTtl()
     */
    public function testSetDefaultTtl(): void
    {
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        
        $this->assertNull($pool->setDefaultTtl(10));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::switchAdapter()
     */
    public function testSwitchAdapter(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapterSwitch = $this->getMockedAdapter();
        
        $pool = $this->getPool($adapter);
        
        $this->assertNull($pool->switchAdapter($adapterSwitch));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getItem()
     */
    public function testGetItem(): void
    {
        $item = $this->getMockedCacheItem("foo", "bar", true, $this);
        $itemSerialized = \serialize($item);
        
        $adapter = $this->getMockedAdapter();
        $adapter->method("get")->with("foo")->will($this->returnValue($itemSerialized));
        
        $pool = $this->getPool($adapter);
        
        $this->assertInstanceOf(CacheItemInterface::class, $pool->getItem("foo"));
        $this->assertEquals($item, $pool->getItem("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getItem()
     */
    public function testGetItemWhenNotFounded(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("get")->with("foo")->will($this->returnValue(null));
        
        $pool = $this->getPool($adapter);
        
        $this->assertInstanceOf(CacheItemInterface::class, $pool->getItem("foo"));
        $this->assertEquals(new CacheItem("foo"), $pool->getItem("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getItems()
     */
    public function testGetItems(): void
    {
        $adapter = $this->getMockedAdapter();

        $generator = $this->getGenerator([
            "foo" => \serialize($this->getMockedCacheItem("foo", "bar", true, $this)),
            "bar" => null
        ]);
        
        $adapter
            ->method("getMultiple")
            ->with(["foo", "bar"])
            ->will($this->returnValue($generator));
        
        $expected = [
            "foo" => $this->getMockedCacheItem("foo", "bar", true, $this),
            "bar" => new CacheItem("bar")
        ];
            
        $pool = $this->getPool($adapter);
        
        $items = $pool->getItems(["foo", "bar"]);
        
        $this->assertEquals($expected, $items);
        foreach ($items as $key => $item) {
            $this->assertInstanceOf(CacheItemInterface::class, $item);
        }
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getItems()
     */
    public function testGetItemsWithNoKeysGiven(): void
    {
        $expected = [];
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        
        $this->assertSame($expected, $pool->getItems());
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::hasItem()
     */
    public function testHasItem(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("exists")->with("foo")->will($this->returnValue(true));
        
        $pool = $this->getPool($adapter);
        
        $this->assertSame(true, $pool->hasItem("foo"));
        
        $adapter = $this->getMockedAdapter();
        $adapter->method("exists")->with("bar")->will($this->returnValue(false));
        
        $pool = $this->getPool($adapter);
        
        $this->assertFalse($pool->hasItem("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::clear()
     */
    public function testClear(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("clear")->with(null)->will($this->returnValue(true));
        
        $pool = $this->getPool($adapter);
        
        $this->assertTrue($pool->clear());
        $this->assertSame([], $this->getDeferredList($pool));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::clear()
     */
    public function testClearOnError(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("clear")->with(null)->will($this->returnValue(false));
        
        $pool = $this->getPool($adapter);
        
        $this->assertFalse($pool->clear());
        $this->assertSame([], $this->getDeferredList($pool));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::deleteItem()
     */
    public function testDelete(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("del")->with("foo")->will($this->returnValue(true));
        
        $pool = $this->getPool($adapter);
        
        $this->assertTrue($pool->deleteItem("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::deleteItem()
     */
    public function testDeleteOnError(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("del")->with("foo")->will($this->returnValue(false));
        
        $pool = $this->getPool($adapter);
        
        $this->assertFalse($pool->deleteItem("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::deleteItem()
     */
    public function testDeleteItems(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("delMultiple")->with(["foo", "bar"])->will($this->returnValue(["foo" => true, "bar" => true]));
        
        $pool = $this->getPool($adapter);
        
        $this->assertTrue($pool->deleteItems(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::deleteItem()
     */
    public function testDeleteItemsOnError(): void
    {
        $adapter = $this->getMockedAdapter();
        $adapter->method("delMultiple")->with(["foo", "bar"])->will($this->returnValue(["foo" => true, "bar" => false]));
        
        $pool = $this->getPool($adapter);
        
        $this->assertFalse($pool->deleteItems(["foo", "bar"]));
    }
    
    /**
     * Cannot test explicitly DateTimeInterface here because of the latency and the precision of DateTime
     * @see \ZoeTest\Component\Cache\CachePoolTest::testGetTtlOnDateTimeInterface()
     * 
     * @see \Zoe\Component\Cache\CachePool::save()
     */
    public function testSave(): void
    {
        $multiple = function(bool $serialize, ...$items): array {
            $itemsCollection = [];
            foreach ($items as $item) {
                $itemsCollection[] = ($serialize) ? \serialize($item) : $item;
            }
            
            return $itemsCollection;
        };
        
        $adapters = [];
        foreach (
            $multiple(true, 
                $this->getCacheItemInstance("foo", "bar", true),
                $this->getCacheItemInstance("foo", "bar", true, null)
        ) as $item) {
            $adapter = $this->getMockedAdapter();
            $adapter
                ->method("set")
                ->with("foo", $item)
                ->will($this->returnValue(true));
            $adapters[] = $adapter;
        }

        $items = $multiple(false,
            $this->getCacheItemInstance("foo", "bar", false),
            $this->getCacheItemInstance("foo", "bar", false, null)
        );
        
        foreach ($adapters as $index => $adapter) {
            $pool = $this->getPool($adapter);
            $this->assertTrue($pool->save($items[$index]));
        }
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::saveDeferred()
     */
    public function testSaveDeferred(): void
    {
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        
        $this->assertTrue($pool->saveDeferred($this->getMockedCacheItem("foo", "bar", false, $this)));
        $this->assertCount(1, $this->getDeferredList($pool));
        $this->assertInstanceOf(CacheItemInterface::class, $this->getDeferredList($pool)["foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::commit()
     */
    public function testCommit(): void
    {
        $items = [
            "foo" => ["value" => \serialize($this->getCacheItemInstance("foo", "bar", true)), "ttl" => null],
            "bar" => ["value" => \serialize($this->getCacheItemInstance("bar", "foo", true)), "ttl" => null]
        ];
        
        $adapter = $this->getMockedAdapter();
        $adapter->method("setMultiple")->with($items)->will($this->returnValue(["foo" => true, "bar" => true]));
        
        $pool = $this->getPool($adapter);
        
        $items = [
            $this->getCacheItemInstance("foo", "bar", false),
            $this->getCacheItemInstance("bar", "foo", false)
        ];
        
        $pool->saveDeferred($items[0]);
        $pool->saveDeferred($items[1]);
        
        $this->assertCount(2, $this->getDeferredList($pool));
        $this->assertTrue($pool->commit());
        $this->assertEmpty($this->getDeferredList($pool));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::commit()
     */
    public function testCommitWithError(): void
    {
        $items = [
            "foo" => ["value" => \serialize($this->getCacheItemInstance("foo", "bar", true)), "ttl" => null],
            "bar" => ["value" => \serialize($this->getCacheItemInstance("bar", "foo", true)), "ttl" => null]
        ];
        
        $adapter = $this->getMockedAdapter();
        $adapter->method("setMultiple")->with($items)->will($this->returnValue(["foo" => true, "bar" => false]));
        
        $pool = $this->getPool($adapter);
        
        $items = [
            $this->getCacheItemInstance("foo", "bar", false),
            $this->getCacheItemInstance("bar", "foo", false)
        ];
        
        $pool->saveDeferred($items[0]);
        $pool->saveDeferred($items[1]);
        
        $this->assertCount(2, $this->getDeferredList($pool));
        $this->assertFalse($pool->commit());
        $this->assertCount(1, $this->getDeferredList($pool));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::commit()
     */
    public function testCommitWhenDeferredListIsEmpty(): void
    {
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        
        $this->assertEmpty($this->getDeferredList($pool));
        $this->assertTrue($pool->commit());
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getTtl()
     */
    public function testGetTtlOnDateTimeInterface(): void
    {
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $reflection = new \ReflectionClass($pool);
        $item = $this->getCacheItemInstance("foo", "bar", false, 1);
        
        $this->assertSame(1, $this->reflection_callMethod($pool, $reflection, "getTtl", $item));
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getTtl()
     */
    public function testGetTtlWithDefaultTtl(): void
    {
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $pool->setDefaultTtl(10);
        $reflection = new \ReflectionClass($pool);
        $item = $this->getCacheItemInstance("foo", "bar", false, null);
        
        $this->assertSame(10, $this->reflection_callMethod($pool, $reflection, "getTtl", $item));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\CachePool::validateKey()
     */
    public function testExceptionWhenKeyIsTooLong(): void
    {
        $key = \str_repeat("f", 65);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' cannot exceed 64 characters");
        
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $reflection = new \ReflectionClass($pool);
        
        $this->reflection_callMethod($pool, $reflection, "validateKey", $key, InvalidArgumentException::class);
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::validateKey()
     */
    public function testExceptionWhenKeyContainsReservedCharacters(): void
    {
        $key = "foo{}()/\\@:";
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key '{$key}' contains reserved characters '{}()/\\@:'");
        
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $reflection = new \ReflectionClass($pool);
        
        $this->reflection_callMethod($pool, $reflection, "validateKey", $key, InvalidArgumentException::class);
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::validateKey()
     */
    public function testExceptionWhenKeyContainsInvalidsCharacters(): void
    {
        $key = "é";
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Key 'é' contains invalids characters");
        
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $reflection = new \ReflectionClass($pool);
        
        $this->reflection_callMethod($pool, $reflection, "validateKey", $key, InvalidArgumentException::class);
    }
    
    /**
     * @see \Zoe\Component\Cache\CachePool::getTtl()
     */
    public function testExceptionGetTtlWhenCacheItemExpirationIsInvalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This cache item 'foo' has an invalid expiration time");
        
        $invalidCacheItemMocked = $this
                                    ->getMockBuilder(CacheItem::class)
                                    ->disableOriginalConstructor()
                                    ->setMethods(["getExpiration", "getKey"])
                                    ->getMock();
        $invalidCacheItemMocked->method("getExpiration")->will($this->returnValue("invalid"));
        $invalidCacheItemMocked->method("getKey")->will($this->returnValue("foo"));
        
        $adapter = $this->getMockedAdapter();
        $pool = $this->getPool($adapter);
        $reflection = new \ReflectionClass($pool);
        
        $this->reflection_callMethod($pool, $reflection, "getTtl", $invalidCacheItemMocked);
    }
    
    /**
     * Get a cache pool instance
     * 
     * @param AdapterInterface $adapter
     *   Adapter to attached to the pool
     *   
     * @return CacheItemPoolInterface
     *   CachePool instance
     */
    private function getPool(AdapterInterface $adapter): CacheItemPoolInterface
    {
        $pool = new CachePool($adapter);
        
        return $pool;
    }
    
    /**
     * Get the deferred list of a cache pool
     * 
     * @param CacheItemPoolInterface $pool
     *   Cache pool instance
     * 
     * @return array
     *   Deferred list of the cache pool
     */
    private function getDeferredList(CacheItemPoolInterface $pool): array
    {
        $reflection = new \ReflectionClass($pool);
        
        return $this->reflection_getPropertyValue($pool, $reflection, "deferred");
    }
    
}
