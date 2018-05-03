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

namespace ZoeTest\Component\Cache\Adapter;

use Zoe\Component\Cache\Adapter\RedisCacheAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use Zoe\Component\Internal\GeneratorTrait;
use ZoeTest\Component\Cache\AbstractCacheTestCase;

/**
 * RedisCacheAdapter testcase
 * 
 * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisCacheAdapterTest extends AbstractCacheTestCase implements AdapterTestConfiguration
{
    
    use GeneratorTrait;
    
    /**
     * Redis real connection
     * 
     * @var \Redis[]
     */
    private static $redis;
    
    /**
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        foreach (self::REDIS_OPTIONS as $redis => $options) {
            ${$redis} = new \Redis();
            try {
                ${$redis}->connect(self::REDIS_HOST, self::REDIS_PORT);
            } catch (\RedisException $e) {
                self::markTestSkipped("Redis connection cannot be established. RedisCacheAdapter testcase skipped");
            }
            if(null !== $options) {
                foreach ($options as $option => $value)
                    ${$redis}->setOption($option, $value);
            }
            self::$redis[$redis] = ${$redis};
        }
    }
    
    /**
     * {@inheritdoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->flushAll();
            $redis->__destruct();
            
            $redis = null;            
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        $this->callOnAllRedis(function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->flushAll();            
        });
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::get()
     */
    public function testGet(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->set("foo", "bar");
            
            $this->assertSame("bar", $adapter->get("foo"));
            $this->assertSame(null, $adapter->get("bar"));
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::set()
     */
    public function testSet(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $this->assertTrue($adapter->set("bar", "foo", null));
            $this->assertTrue($adapter->set("foo", "bar", 2));
        };
        
        self::callOnAllRedis($actions);
        
        $actions = function(MockObject $redis): void {
            $redis->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar"], ["bar", "foo"])->will($this->onConsecutiveCalls(true, false));
            $redis->expects($this->exactly(2))->method("setex")->withConsecutive(["foo", 2, "bar"], ["bar", 2, "foo"])->will($this->onConsecutiveCalls(true, false));
        };
        
        $adapter = $this->initializeAdapterWithMockedRedis($actions);
        
        $this->assertTrue($adapter->set("foo", "bar", null));
        $this->assertFalse($adapter->set("bar", "foo", null));
        $this->assertTrue($adapter->set("foo", "bar", 2));
        $this->assertFalse($adapter->set("bar", "foo", 2));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->set("foo", "bar");
            
            $this->assertTrue($adapter->delete("foo"));
            $this->assertFalse($adapter->delete("bar"));            
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::exists()
     */
    public function testExists(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void  {
            $redis->set("foo", "bar");
            
            $this->assertTrue($adapter->exists("foo"));
            $this->assertFalse($adapter->exists("bar"));
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->set("foo", "bar");
            $redis->set("bar", "foo");
            
            $expected = $this->getGenerator(["foo" => "bar", "bar" => "foo", "poz" => null]);

            $this->assertTrue($this->assertGeneratorEquals($expected, $adapter->getMultiple(["foo", "bar", "poz"])));            
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $values = [
            "foo"   =>  (object) ["key" => "foo", "value" => "bar", "ttl" => null],
            "bar"   =>  (object) ["key" => "bar", "value" => "foo", "ttl" => null],
            "moz"   =>  (object) ["key" => "moz", "value" => "bar", "ttl" => 2],
            "poz"   =>  (object) ["key" => "poz", "value" => "foo", "ttl" => 2],
        ];
        
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter) use ($values): void {
            $this->assertNull($adapter->setMultiple($values));
        };
        
        self::callOnAllRedis($actions);
        
        $actions = function(MockObject $redis): void {
            $redis->expects($this->once())->method("multi")->with(\Redis::PIPELINE);
            $redis->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar"], ["bar", "foo"]);
            $redis->expects($this->exactly(2))->method("setex")->withConsecutive(["moz", 2, "bar"], ["poz", 2, "foo"]);
            $redis->expects($this->once())->method("exec")->will($this->returnValue([true, false, true, false]));
        };
        
        $adapter = $this->initializeAdapterWithMockedRedis($actions);
        $this->assertSame(["bar", "poz"], $adapter->setMultiple($values));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter): void {
            $redis->set("foo", "bar");
            
            $this->assertSame(["bar"], $adapter->deleteMultiple(["foo", "bar"]));
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisCacheAdapter::clear()
     */
    public function testClear(): void
    {
        $this->_testIs(self::STUPID);
        
        $actions = function(\Redis $redis, RedisCacheAdapter $adapter, string $name): void {
            $values = function(int $many): array {
                $values = [];
                for ($i = 0; $i <= $many; $i++) {
                    if($i % 2 === 0)
                        $values["Foo_Key_{$i}"] = "Foo";
                    else
                        $values["Bar_Key_{$i}"] = "Bar";
                }
                
                return $values;
            };
            
            $valuesStored = $values(4000);
            foreach ($valuesStored as $key => $value) {
                $redis->set($key, $value);
            }
            $this->assertTrue($adapter->clear(null));
            foreach ($valuesStored as $key => $value) {
                $this->assertSame(0, $redis->exists($key), "{$key} exists into Redis store for testing redis {$name}");
            }
            
            $valuesStored = $values(4000);
            foreach ($valuesStored as $key => $value) {
                $redis->set($key, $value);
            }
            $this->assertTrue($adapter->clear("Bar"));
            foreach ($valuesStored as $key => $value) {
                if(false !== \strpos($key, "Foo"))
                    $this->assertSame(1, $redis->exists($key), "{$key} MUST exists into Redis store for testing redis {$name}");
                else
                    $this->assertSame(0, $redis->exists($key), "{$key} exists into Redis store for testing redis {$name}");
            }
            
            if($name === "REDIS_PREFIXED")
                $this->assertSame("PREFIX_", $redis->getOption(\Redis::OPT_PREFIX));
        };
        
        self::callOnAllRedis($actions);
    }
    
    /**
     * Initialize a new RedisCacheAdapter with a mocked redis into it
     * 
     * @param \Closure|null $action
     *   Actions called before redis is setted into the adapter
     * 
     * @return RedisCacheAdapter
     *   RedisCacheAdapter with mocked redis setted into it
     */
    private function initializeAdapterWithMockedRedis(?\Closure $actions): RedisCacheAdapter
    {
        $redis = $this->getMockBuilder(\Redis::class)->getMock();
        if(null !== $actions)
            $actions->call($this, $redis);
        
        return new RedisCacheAdapter($redis);
    }
    
    /**
     * Make a call for each registered redis instances
     * 
     * @param callable $actions
     *   Test to execute. Take as first parameter the redis connection and as second and instantiated RedisCacheAdapter with the setted redis
     */
    private static function callOnAllRedis(callable $actions): void
    {
        foreach (self::$redis as $name => $redis) {
            $adapter = new RedisCacheAdapter($redis);
            \call_user_func($actions, $redis, $adapter, $name);
        }
    }
    
}
