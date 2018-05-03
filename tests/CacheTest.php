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

use Zoe\Component\Cache\Cache;
use Zoe\Component\Cache\Adapter\CacheAdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\Internal\GeneratorTrait;
use Zoe\Component\Cache\Exception\PSR16\InvalidArgumentException;

/**
 * Cache testcase
 * 
 * @see \Zoe\Component\Cache\Cache
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheTest extends AbstractCacheTestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Cache\Cache::get()
     */
    public function testGet(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter
                ->expects($this->exactly(6))
                ->method("get")
                ->withConsecutive([$prefix("foo")], [$prefix("bar")], [$prefix("moz")], [$prefix("poz")], [$prefix("loz")], [$prefix("goz")])
                ->will($this->onConsecutiveCalls("bar", null, null, "N;", "b:0;", \serialize(new \stdClass())));   
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertSame("bar", $cache->get("foo"));
        $this->assertNull($cache->get("bar"));
        $this->assertSame("Default", $cache->get("moz", "Default"));
        $this->assertSame(null, $cache->get("poz"));
        $this->assertSame(false, $cache->get("loz"));
        $this->assertEquals(new \stdClass(), $cache->get("goz"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::set()
     */
    public function testSet(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter
                ->expects($this->exactly(4))
                ->method("set")
                ->withConsecutive(
                    [$prefix("foo"), "bar", null], 
                    [$prefix("bar"), \serialize(new \stdClass()), 2000],
                    [$prefix("moz"), "poz", 2000],
                    [$prefix("poz"), "moz", null]
                )
                ->will($this->onConsecutiveCalls(true, true, true, false));
        };
        
        $adapter = $this->initializeCache($actions);
        
        $this->assertTrue($adapter->set("foo", "bar"));
        $this->assertTrue($adapter->set("bar", new \stdClass(), \DateInterval::createFromDateString("2000 seconds")));
        $this->assertTrue($adapter->set("moz", "poz", 2000));
        $this->assertFalse($adapter->set("poz", "moz"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::delete()
     */
    public function testDelete(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter->expects($this->exactly(2))->method("delete")->withConsecutive([$prefix("foo")], [$prefix("bar")])->will($this->onConsecutiveCalls(true, false));   
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertTrue($cache->delete("foo"));
        $this->assertFalse($cache->delete("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::delete()
     */
    public function testClear(): void 
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter->expects($this->exactly(2))->method("clear")->with(Cache::PSR16_CACHE_FLAG)->will($this->onConsecutiveCalls(true, false));   
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertTrue($cache->clear());
        $this->assertFalse($cache->clear());
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $keys = [
                Cache::PSR16_CACHE_FLAG."foo", 
                Cache::PSR16_CACHE_FLAG."bar", 
                Cache::PSR16_CACHE_FLAG."loz", 
                Cache::PSR16_CACHE_FLAG."moz",
                Cache::PSR16_CACHE_FLAG."noz"
            ];
            $returned = $this->getGenerator([
                $keys[0]   =>  "bar", 
                $keys[1]   =>  null,
                $keys[2]   =>  "b:0;",
                $keys[3]   =>  "N;",
                $keys[4]   =>  \serialize(new \stdClass())
            ]);
            $adapter
                ->expects($this->once())
                ->method("getMultiple")
                ->with($keys)
                ->will($this->returnValue($returned));   
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertEquals([
            "foo"   => "bar", 
            "bar"   => "Default",
            "loz"   =>  false,
            "moz"   =>  null,
            "noz"   =>  new \stdClass()
        ], $cache->getMultiple(["foo", "bar", "loz", "moz", "noz"], "Default"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive([
                    [
                        (object) ["key" => $prefix("foo"), "value" => "bar", "ttl" => null],
                        (object) ["key" => $prefix("bar"), "value" => "foo", "ttl" => null],
                        (object) ["key" => $prefix("moz"), "value" => \serialize(new \stdClass()), "ttl" => null]
                    ],
                ],
                [
                    [
                        (object) ["key" => $prefix("foo"), "value" => "bar", "ttl" => 2000],
                        (object) ["key" => $prefix("bar"), "value" => "foo", "ttl" => 2000],
                        (object) ["key" => $prefix("moz"), "value" => \serialize(new \stdClass()), "ttl" => 2000]
                    ],
                ],
                [
                    [
                        (object) ["key" => $prefix("foo"), "value" => "bar", "ttl" => 2000],
                        (object) ["key" => $prefix("bar"), "value" => "foo", "ttl" => 2000],
                        (object) ["key" => $prefix("moz"), "value" => \serialize(new \stdClass()), "ttl" => 2000]
                    ],
                ]
                )
            ->will($this->onConsecutiveCalls(null, [$prefix("foo"), $prefix("bar")], null));
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertTrue($cache->setMultiple(["foo" => "bar", "bar" => "foo", "moz" => new \stdClass()]));
        $this->assertFalse($cache->setMultiple(["foo" => "bar", "bar" => "foo", "moz" => new \stdClass()], 2000));
        $this->assertTrue($cache->setMultiple(["foo" => "bar", "bar" => "foo", "moz" => new \stdClass()], \DateInterval::createFromDateString("2000 seconds")));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void  {
            $adapter
                ->expects($this->exactly(2))
                ->method("deleteMultiple")
                ->withConsecutive(
                    [
                        [$prefix("foo"), $prefix("bar")]
                    ],
                    [
                        [$prefix("foo"), $prefix("bar")]
                    ]
                )->will($this->onConsecutiveCalls(null, [$prefix("foo"), $prefix("bar")]));   
        };
        
        $cache = $this->initializeCache($actions);

        $this->assertTrue($cache->deleteMultiple(["foo", "bar"]));
        $this->assertFalse($cache->deleteMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::has()
     */
    public function testHas(): void
    {
        $actions = function(MockObject $adapter, callable $prefix): void  {
            $adapter->expects($this->exactly(2))->method("exists")->withConsecutive([$prefix("foo")], [$prefix("bar")])->will($this->onConsecutiveCalls(true, false)); 
        };
        
        $cache = $this->initializeCache($actions);
        
        $this->assertTrue($cache->has("foo"));
        $this->assertFalse($cache->has("bar"));
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\Cache::get()
     */
    public function testExceptionGetOnInvalid(): void
    {
        $this->expectException(\TypeError::class);
        $this->expectExceptionMessage("Key given MUST be a/an 'string'. 'array' given");
        
        $cache = $this->initializeCache();
        
        $cache->get(["foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::get()
     */
    public function testExceptionGetWhenInvalidKeyIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This key 'Fooé' is invalid. Supported characters : 'A-Za-z0-9_.{}()/\@:'");
        
        $cache = $this->initializeCache();
        
        $cache->get("Fooé");
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::get()
     */
    public function testExceptionGetWhenReservedCharacterIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This key 'Foo@' contains reserved characters : '{}()/\@:'");
        
        $cache = $this->initializeCache();
        
        $cache->get("Foo@");
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::get()
     */
    public function testExceptionGetWhenMaxCharactersAllowedIsReached(): void
    {
        $key = \str_repeat("foo", 64);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Max characters allowed for cache key '{$key}' allowed reached. Max setted to '64'");
        
        $cache = $this->initializeCache();
        
        $cache->get($key);
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::set()
     */
    public function testExceptionSetWhenInvalidTtlIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ttl MUST be an instance of DateInterval or an integer (time in seconds), or null");
        
        $cache = $this->initializeCache();
        
        $cache->set("foo", "bar", true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::set()
     */
    public function testExceptionSetWhenAnInvalidValueIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Given value is not handled by this Cache implementation. See message : Serialization of 'class@anonymous' is not allowe");
        
        $cache = $this->initializeCache();
        
        $cache->set("foo", new class(){});
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::setMultiple()
     */
    public function testExceptionSetMultipleWhenAnInvalidKeyIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("This key 'bar@' contains reserved characters : '{}()/\@:'");
        
        $cache = $this->initializeCache();
        
        $cache->setMultiple(["foo" => "bar", "bar@" => "foo"]);
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::setMultiple()
     */
    public function testExceptionSetMultipleWhenAnInvalidTtlIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Ttl MUST be an instance of DateInterval or an integer (time in seconds), or null");
        
        $cache = $this->initializeCache();
        
        $cache->setMultiple(["foo" => "bar", "bar" => "foo"], true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Cache::setMultiple()
     */
    public function testExceptionSetMultipleWhenAnInvalidValueIsGiven(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Given value is not handled by this Cache implementation. See message : Serialization of 'class@anonymous' is not allowe");
        
        $cache = $this->initializeCache();
        
        $cache->setMultiple(["foo" => new class(){}]);
    }
    
    /**
     * Initialize a new Cache with a adapter mocked setted
     * 
     * @param \Closure $actions
     *   Action to call on the setted adapter. Takes as parameters the mocked adapter and a helper to prefix keys 
     * 
     * @return Cache
     *   Cache intitialized
     */
    private function initializeCache(?\Closure $actions = null): Cache
    {
        $adapter = $this->getMockBuilder(CacheAdapterInterface::class)->getMock();
        $prefix = function(string $key): string {
            return Cache::PSR16_CACHE_FLAG . $key;
        };
        if(null !== $actions)
            $actions->call($this, $adapter, $prefix);
        
        return new Cache($adapter);
    }
    
}
