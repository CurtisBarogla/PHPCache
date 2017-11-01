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

use ZoeTest\Component\Cache\CacheTestCase;
use ZoeTest\Component\Cache\Helpers\Traits\GeneratorTrait;
use Zoe\Component\Cache\Adapter\AdapterInterface;
use Zoe\Component\Cache\Adapter\RedisAdapter;

/**
 * RedisAdapter testcase
 * 
 * @see \Zoe\Component\Cache\Adapter\RedisAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class RedisAdapterTest extends CacheTestCase
{
    
    use GeneratorTrait;
    
    /**
     * If a prefix must be added to the redis option
     * 
     * @var string
     */
    private const USE_PREFIX = true;
    
    /**
     * Redis instance
     * 
     * @var \Redis
     */
    private static $redis;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        self::$redis = new \Redis();
        self::$redis->connect(self::REDIS_HOST, self::REDIS_PORT);
        
        if(self::USE_PREFIX)
            self::$redis->setOption(\Redis::OPT_PREFIX, self::REDIS_OPTIONS["prefix"]);
        
        self::$redis->flushAll();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    public function setUp(): void
    {
        self::$redis->flushAll();
        
        // fixtures
        self::$redis->set("foo", "bar");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    public function tearDown(): void
    {
        self::$redis->flushAll();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        self::$redis->flushAll();
        self::$redis->close();
        self::$redis->__destruct();
        self::$redis = null;
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter
     */
    public function testInterface(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertInstanceOf(AdapterInterface::class, $adapter);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::get()
     */
    public function testGet(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertSame("bar", $adapter->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::get()
     */
    public function testGetWhenValueIsNotStored(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertNull($adapter->get("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $adapter = $this->getAdapter();
        self::$redis->set("bar", "foo");
        $expected = $this->getGenerator(["foo" => "bar", "bar" => "foo"]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $adapter->getMultiple(["foo", "bar"])));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::getMultiple()
     */
    public function testGetMultipleWithError(): void
    {
        $adapter = $this->getAdapter();
        
        $expected = $this->getGenerator(["foo" => "bar", "bar" => null]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $adapter->getMultiple(["foo", "bar"])));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::set()
     */
    public function testSet(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertTrue($adapter->set("foo", "bar", null));
        
        $adapter = $this->getAdapter();
        
        $this->assertTrue($adapter->set("foo", "bar", 1));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $adapter = $this->getAdapter();
        $expected = ["foo" => true, "bar" => true];
        $values = [
            "foo" => ["value" => "bar", "ttl" => null], 
            "bar" => ["value" => "foo", "ttl" => 1]
        ];
        $this->assertSame($expected, $adapter->setMultiple($values));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::del()
     */
    public function testDel(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertTrue($adapter->del("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::del()
     */
    public function testDelWhenStoreReturnFalse(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertFalse($adapter->del("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::delMultiple()
     */
    public function testDelMultiple(): void
    {
        $adapter = $this->getAdapter();
        $expected = ["foo" => true, "bar" => false];
        
        $this->assertSame($expected, $adapter->delMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::exists()
     */
    public function testExists(): void
    {
        $adapter = $this->getAdapter();
        
        $this->assertTrue($adapter->exists("foo"));
        
        $adapter = $this->getAdapter();
        
        $this->assertFalse($adapter->exists("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::clear()
     */
    public function testClear(): void
    {
        $adapter = $this->getAdapter();
        
        self::$redis->set("bar", "foo");
        
        $this->assertTrue($adapter->clear());
        
        $this->assertFalse(self::$redis->get("foo"));
        $this->assertFalse(self::$redis->get("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\RedisAdapter::clear()
     */
    public function testClearWithPattern(): void
    {
        $adapter = $this->getAdapter();
        
        self::$redis->set("bar", "foo");
        
        $this->assertTrue($adapter->clear("foo"));
        
        $this->assertFalse(self::$redis->get("foo"));
        $this->assertSame("foo", self::$redis->get("bar"));
    }
    
    /**
     * Get an instance of a RedisAdapter
     * 
     * @param \Redis|null
     *   Redis instance or null. If null, will use the global declared one
     * 
     * @return RedisAdapter
     *   RedisAdapter instance
     */
    private function getAdapter(?\Redis $redis = null): RedisAdapter
    {
        if(null === $redis)
            $redis = self::$redis;
        return new RedisAdapter($redis);
    }
    
}
