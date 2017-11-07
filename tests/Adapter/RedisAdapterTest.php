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
class RedisAdapterTest extends AdapterTest
{
    
    /**
     * If the prefix must be setted into the redis instance
     * 
     * @var bool
     */
    private const USE_PREFIX = true;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        $redis = new \Redis();
        if(!@$redis->connect(self::REDIS_HOST, self::REDIS_PORT))
            self::markTestSkipped("No redis server valid found");
        
        if(self::USE_PREFIX)
            $redis->setOption(\Redis::OPT_PREFIX, self::REDIS_OPTIONS["prefix"]);
        
        $redis->flushAll();
            
        self::$store = $redis;
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        self::$store->flushAll();
        
        // set up a fixture key
        self::$store->set("foo", "bar");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        self::$store->flushAll();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        self::$store->flushAll();
        self::$store->close();
        self::$store = null;
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Adapter\AdapterTest::testClear()
     */
    public function testClear(): void
    {
        // with pattern
        self::$store->set("bar", "foo");
        self::$store->set("foz", "moz");
        
        $this->assertTrue($this->getAdapter()->clear("fo"));
        
        $this->assertTrue(self::$store->exists("bar"));
        $this->assertFalse(self::$store->exists("foz"));
        $this->assertFalse(self::$store->exists("foo"));
        
        // with no pattern
        self::$store->set("bar", "foo");
        self::$store->set("foz", "moz");
        
        $this->assertTrue($this->getAdapter()->clear());
        
        $this->assertFalse(self::$store->exists("bar"));
        $this->assertFalse(self::$store->exists("foz"));
        $this->assertFalse(self::$store->exists("foo"));
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Adapter\AdapterTest::getAdapter()
     */
    protected function getAdapter(): AdapterInterface
    {
        return new RedisAdapter(self::$store);
    }

}
