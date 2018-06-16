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
use function NessTest\Component\Cache\config\getTestConfiguration;
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\Adapter\RedisCacheAdapter;

/**
 * RedisCacheAdapter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCacheAdapterTest extends CacheTestCase
{
    
    /**
     * Redis connections
     * 
     * @var \Redis[]
     */
    private static $redisConnections = [];
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\class_exists(\Redis::class))
            self::markTestSkipped("No redis class found");
        
        foreach (getTestConfiguration("REDIS_CONFIGS") as $index => $value) {
            try {
                $redis = new \Redis();
                $redis->connect($value["host"], $value["port"]);
                if(isset($value["options"])) {
                    foreach ($value["options"] as $option => $value) {
                        $redis->setOption($option, $value);
                    }
                }
                self::$redisConnections[$index] = $redis;                    
            } catch (\RedisException $e) {
                self::markTestIncomplete("Connection to redis {$index} cannot be reached");
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        foreach (self::$redisConnections as $connection) {
            $connection->__destruct();
        }
        self::$redisConnections = null;
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::get()
     */
    public function testGet(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
             $redis->set("foo", "bar");
             $this->assertSame("bar", $adapter->get("foo"));
             $this->assertNull($adapter->get("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $redis->set("foo", "bar");
            $redis->set("bar", "foo");
            
            $values = $adapter->getMultiple(["foo", "bar", "moz"]);
            
            $this->assertSame("bar", $values[0]);
            $this->assertSame("foo", $values[1]);
            $this->assertNull($values[2]);
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::set()
     */
    public function testSet(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $this->assertTrue($adapter->set("foo", "bar", null));
            $this->assertSame("bar", $redis->get("foo"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
             $this->assertNull($adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => 1]]));
             $this->assertSame("bar", $redis->get("foo"));
             $this->assertSame("foo", $redis->get("bar"));
             $this->assertSame(1, $redis->ttl("bar"));
        });
        
        $redis = $this->getMockedRedis(function(MockObject $redis): void {
            $redis->expects($this->once())->method("exec")->will($this->returnValue(["foo" => true, "bar" => false]));   
        });
        
        $adapter = new RedisCacheAdapter($redis);
        
        $this->assertSame(["bar"], $adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => 1]]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $this->assertFalse($adapter->delete("foo"));
            $redis->set("foo", "bar");
            $this->assertTrue($adapter->delete("foo"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $redis->set("foo", "bar");
            $redis->set("bar", "foo");
            
            $this->assertNull($adapter->deleteMultiple(["foo", "bar"]));
            
            $redis->set("bar", "foo");
            $this->assertSame(["foo"], $adapter->deleteMultiple(["foo", "bar"]));
        });
        
        $redis = $this->getMockedRedis(function(MockObject $redis): void {
            $redis->expects($this->once())->method("exec")->will($this->returnValue([true, false]));   
        });
        
        $adapter = new RedisCacheAdapter($redis);
        
        $this->assertSame(["bar"], $adapter->deleteMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::has()
     */
    public function testHas(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $this->assertFalse($adapter->has("foo"));
            $redis->set("foo", "bar");
            $this->assertTrue($adapter->has("foo"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\RedisCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $this->executeTestOnRedisConnections(function(RedisCacheAdapter $adapter, \Redis $redis, string $identifier): void {
            $values = [
                "foo_foo"   =>  "bar",
                "foo_bar"   =>  "foo",
                "bar_foo"   =>  "bar",
                "bar_bar"   =>  "foo"
            ];
            $reset = function() use ($values, $redis): void {
                foreach ($values as $key => $value)
                    $redis->set($key, $value);
            };
            $reset();
            $this->assertNull($adapter->purge(null));
            foreach ($values as $key => $value)
                $this->assertSame(0, $redis->exists($key));
            $reset();
            $this->assertNull($adapter->purge("foo_"));
            $this->assertSame(0, $redis->exists("foo_foo"));
            $this->assertSame(0, $redis->exists("foo_bar"));
            $this->assertSame(1, $redis->exists("bar_foo"));
            $this->assertSame(1, $redis->exists("bar_bar"));
        });
    }
    
    /**
     * Get a mocked redis connection
     * 
     * @param \Closure|null $action
     *   Action done on the redis connection
     * 
     * @return MockObject
     *   Mocked redis connection
     */
    private function getMockedRedis(?\Closure $action = null): MockObject
    {
        $redis = $this->getMockBuilder(\Redis::class)->getMock();
        if(null !== $action)
            $action->call($this, $redis);
        
        return $redis;
    }
    
    /**
     * Execute a test over all redis connections
     * 
     * @param \Closure $test
     *   Test to perform. 
     *   Takes as first parameter an initialized adapter with redis setted, as second the redis connection, as third the redis identifier
     */
    private function executeTestOnRedisConnections(\Closure $test): void
    {
        foreach (self::$redisConnections as $identifier => $redis) {
            $adapter = new RedisCacheAdapter($redis);
            
            $test->call($this, $adapter, $redis, $identifier);
            
            $redis->flushAll();
        }
    }
    
}
