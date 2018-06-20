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
use Ness\Component\Cache\Exception\InvalidArgumentException;

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
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::getItem()
     */
    public function testGetItem(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("get")->withConsecutive(...$prefixation(["foo", "bar"], CacheItemPool::CACHE_FLAG."prefix_"))
                ->will($this->onConsecutiveCalls('C:35:"Ness\Component\Cache\PSR6\CacheItem":50:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;i:3;}}', null));
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
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::getItems()
     */
    public function testGetItems(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->once())
                ->method("getMultiple")
                ->with([$prefixation("foo", CacheItemPool::CACHE_FLAG), $prefixation("bar", CacheItemPool::CACHE_FLAG)])
                ->will($this->returnValue(['C:35:"Ness\Component\Cache\PSR6\CacheItem":50:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;i:3;}}'], null));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertEmpty($pool->getItems());
        
        $items = $pool->getItems(["foo", "bar"]);
        $foo = $items["foo"];
        $bar = $items["bar"];
        
        $this->assertSame("foo", $foo->getKey());
        $this->assertSame("bar", $foo->get());
        $this->assertTrue($foo->isHit());
        
        $this->assertSame("bar", $bar->getKey());
        $this->assertNull($bar->get());
        $this->assertFalse($bar->isHit());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::hasItem()
     */
    public function testHasItem(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("has")
                ->withConsecutive(...$prefixation(["foo", "bar"], CacheItemPool::CACHE_FLAG))
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
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(CacheItemPool::CACHE_FLAG);
        });
        
        $pool = new CacheItemPool($adapter);
        
        $this->assertTrue($pool->clear());
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::deleteItem()
     */
    public function testDeleteItem(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("delete")
                ->withConsecutive(...$prefixation(["foo", "bar"], CacheItemPool::CACHE_FLAG))
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
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("deleteMultiple")
                ->withConsecutive([[$prefixation("foo", CacheItemPool::CACHE_FLAG), $prefixation("bar", CacheItemPool::CACHE_FLAG)]])
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
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(6))->method("set")->withConsecutive(
                [
                    $prefixation("bar", CacheItemPool::CACHE_FLAG), 
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":48:{a:4:{i:0;s:3:"bar";i:1;s:3:"foo";i:2;b:1;i:3;N;}}', 
                    null 
                ],
                [
                    $prefixation("moz", CacheItemPool::CACHE_FLAG), 
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":50:{a:4:{i:0;s:3:"moz";i:1;s:3:"poz";i:2;b:1;i:3;i:3;}}', 
                    3
                ],
                // default ttl CachePool setted to null
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG), 
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;d:INF;}}', 
                    null
                ],
                // default ttl CachePool setted to 7
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG),
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;d:INF;}}',
                    7
                ],
                // default ttl CachePool setted to a DateInterval
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG),
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;d:INF;}}',
                    7
                ],
                // default ttl CachePool setted to a Datetime
                [
                    $prefixation("foo", CacheItemPool::CACHE_FLAG),
                    'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;d:INF;}}',
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
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::saveDeferred()
     */
    public function testSaveDeferred(): void
    {
        $pool = new CacheItemPool($this->getMockedAdapter());
        
        $this->assertTrue($pool->saveDeferred(new CacheItem("foo")));
    }
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::commit()
     */
    public function testCommit(): void
    {
        $adapter = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $foo = 'C:35:"Ness\Component\Cache\PSR6\CacheItem":52:{a:4:{i:0;s:3:"foo";i:1;s:3:"bar";i:2;b:1;i:3;d:INF;}}';
            $bar = 'C:35:"Ness\Component\Cache\PSR6\CacheItem":48:{a:4:{i:0;s:3:"bar";i:1;s:3:"foo";i:2;b:1;i:3;N;}}';
            $moz = 'C:35:"Ness\Component\Cache\PSR6\CacheItem":50:{a:4:{i:0;s:3:"moz";i:1;s:3:"poz";i:2;b:1;i:3;i:3;}}';
            $adapter
                ->expects($this->exactly(2))
                ->method("setMultiple")
                ->with(
                    [
                        $prefixation("foo", CacheItemPool::CACHE_FLAG) => ["value" => $foo, "ttl" => null],
                        $prefixation("bar", CacheItemPool::CACHE_FLAG) => ["value" => $bar, "ttl" => null],
                        $prefixation("moz", CacheItemPool::CACHE_FLAG) => ["value" => $moz, "ttl" => 3],
                    ]    
                )
                ->will($this->onConsecutiveCalls(null, ["foo"]));
        });
        
        $pool = new CacheItemPool($adapter);
        
        $pool->saveDeferred((new CacheItem("foo"))->set("bar"));
        $pool->saveDeferred((new CacheItem("bar"))->set("foo")->expiresAfter(null));
        $pool->saveDeferred((new CacheItem("moz"))->set("poz")->expiresAfter(3));
        
        $this->assertTrue($pool->commit());
        $this->assertTrue($pool->commit());
        
        $pool->saveDeferred((new CacheItem("foo"))->set("bar"));
        $pool->saveDeferred((new CacheItem("bar"))->set("foo")->expiresAfter(null));
        $pool->saveDeferred((new CacheItem("moz"))->set("poz")->expiresAfter(3));
        
        $this->assertFalse($pool->commit());
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\PSR6\CacheItemPool::__construct()
     */
    public function testExceptionWhenDefaultTtlIsNotAValidType(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Default ttl for CachePool MUST be null, an int (time in seconds), an implementation of DateTimeInterface or a DateInterval. 'string' given");
        
        $pool = new CacheItemPool($this->getMockedAdapter(), "foo");
    }
    
}
