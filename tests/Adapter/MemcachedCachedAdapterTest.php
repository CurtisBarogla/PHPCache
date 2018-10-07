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

namespace NessTest\Component\Cache\Adapter;

use NessTest\Component\Cache\CacheTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\Adapter\MemcachedCacheAdapter;

/**
 * MemcachedCachedAdapter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedCachedAdapterTest extends CacheTestCase
{
    
    /**
     * Memcached connections
     *
     * @var \Memcached[]
     */
    private static $memcachedConnections = [];
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\class_exists("Memcached"))
            self::markTestSkipped("No memcached class found");
            
        foreach (self::getMemcachedConfiguration() as $index => $value) {
            $memcached = new \Memcached();
            $memcached->addServer($value["host"], $value["port"]);
            if(false === $memcached->set("foo", "bar"))
                self::markTestSkipped("Memcached server '{$value["host"]}' on {$value["port"]} port cannot be configured");
            if(isset($value["options"])) {
                foreach ($value["options"] as $option => $value) {
                    $memcached->setOption($option, $value);
                }
            }
            $memcached->flush();
            self::$memcachedConnections[$index] = $memcached;
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        foreach (self::$memcachedConnections as $connection) {
            $connection->quit();
        }
        
        self::$memcachedConnections = null;
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::get()
     */
    public function testGet(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $memcached->set("foo", "bar");
            
            $this->assertSame("bar", $adapter->get("foo"));
            $this->assertNull($adapter->get("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $memcached->set("foo", "bar");
            $memcached->set("bar", "foo");
            
            $this->assertSame(["bar", "foo"], $adapter->getMultiple(["foo", "bar"]));
            $this->assertSame([null, "bar", "foo"], $adapter->getMultiple(["foo", "bar", "moz"]));
            $this->assertSame([null, null, null], $adapter->getMultiple(["moz", "poz", "kek"]));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::set()
     */
    public function testSet(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $this->assertTrue($adapter->set("foo", "bar", 1));
            $this->assertTrue($adapter->set("foo", "bar", null));
            
            $this->assertSame("bar", $memcached->get("foo"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $this->assertNull($adapter->setMultiple([
                "foo"   =>  ["value" => "bar", "ttl" => null],
                "bar"   =>  ["value" => "foo", "ttl" => 1]
            ]));
            
            $this->assertSame("bar", $memcached->get("foo"));
            $this->assertSame("foo", $memcached->get("bar"));
        });
        
        $memcached = $this->getMockedMemcached(function(MockObject $memcached): void {
            $memcached->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar", null], ["bar", "foo", 1])->will($this->onConsecutiveCalls(true, false));  
        });
        
        $adapter = new MemcachedCacheAdapter($memcached);
        
        $this->assertSame(["bar"], $adapter->setMultiple([
            "foo"   =>  ["value" => "bar", "ttl" => null],
            "bar"   =>  ["value" => "foo", "ttl" => 1]
        ]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $memcached->set("foo", "bar");
            
            $this->assertTrue($adapter->delete("foo"));
            $this->assertFalse($adapter->delete("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $memcached->set("foo", "bar");
            $memcached->set("bar", "foo");
            
            $this->assertNull($adapter->deleteMultiple(["foo", "bar"]));
            
            $memcached->set("foo", "bar");
            
            $this->assertSame(["bar"], $adapter->deleteMultiple(["foo", "bar"]));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::has()
     */
    public function testHas(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $memcached->set("foo", "bar");
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertFalse($adapter->has("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\MemcachedCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $this->executeTestOnMemcachedConnections(function(MemcachedCacheAdapter $adapter, \Memcached $memcached, string $identifier): void {
            $keys = ["foo_foo", "bar_FOO", "foo_bar" , "bar_BAR"];
            $reset = function() use ($keys, $memcached): void {
                foreach ($keys as $key)
                    $memcached->set($key, "foo");
            };
            $reset();
            $this->assertNull($adapter->purge(null));
            foreach ($keys as $key)
                $this->assertFalse($memcached->get($key));
            
            $reset();
            $this->assertNull($adapter->purge("foo_"));
            $this->assertFalse($memcached->get("foo_foo"));
            $this->assertFalse($memcached->get("foo_bar"));
            $this->assertSame("foo", $memcached->get("bar_FOO"));
        });
    }
    
    /**
     * Get a mocked memcached connection
     *
     * @param \Closure|null $action
     *   Action done on the memcached connection
     *
     * @return MockObject
     *   Mocked memcached connection
     */
    private function getMockedMemcached(?\Closure $action = null): MockObject
    {
        $memcached = $this->getMockBuilder(\Memcached::class)->getMock();
        if(null !== $action)
            $action->call($this, $memcached);
            
        return $memcached;
    }
    
    /**
     * Execute a test over all memcached connections
     *
     * @param \Closure $test
     *   Test to perform.
     *   Takes as first parameter an initialized adapter with redis setted, as second the memcached connection, as third the memcached identifier
     */
    private function executeTestOnMemcachedConnections(\Closure $test): void
    {
        foreach (self::$memcachedConnections as $identifier => $memcached) {
            $adapter = new MemcachedCacheAdapter($memcached);
            
            $test->call($this, $adapter, $memcached, $identifier);
            
            $memcached->flush();
        }
    }
    
    /**
     * Memcached instances configuration
     *
     * @return array
     *   Memcached configurations
     */
    public static function getMemcachedConfiguration(): array
    {
        return [
            "memcached_without_prefix"  =>  [
                "host"                      =>  "127.0.0.1",
                "port"                      =>  11211
            ],
            "memcached_with_prefix"     =>  [
                "host"                      =>  "127.0.0.1",
                "port"                      =>  11211,
                "options"                   =>  [
                    \Memcached::OPT_PREFIX_KEY  =>  "prefix_"
                ]
            ]
        ];
    }
    
}
