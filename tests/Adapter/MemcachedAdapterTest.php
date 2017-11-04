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
use Zoe\Component\Cache\Adapter\MemcachedAdapter;

/**
 * MemcachedAdapter testcase
 * 
 * @see \Zoe\Component\Cache\Adapter\MemcachedAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedAdapterTest extends AdapterTest
{
    
    /**
     * If the prefix must be setted into the memcached instance
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
        $memcached = new \Memcached();
        if(!@$memcached->addServer(self::MEMCACHED_HOST, self::MEMCACHED_PORT))
            self::markTestSkipped("No memcached server valid found");
        
        if(self::USE_PREFIX)
            $memcached->setOption(\Memcached::OPT_PREFIX_KEY, self::PREFIX);
        
        $memcached->flush();
            
        self::$store = $memcached;
        
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        self::$store->flush();
        
        self::$store->set("foo", "bar");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        self::$store->flush();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDownAfterClass()
     */
    public static function tearDownAfterClass(): void
    {
        self::$store->flush();
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
        
        $this->assertSame("foo", self::$store->get("bar"));
        $this->assertFalse(self::$store->get("foz"));
        $this->assertFalse(self::$store->get("foo"));
        
        // with no pattern
        self::$store->set("bar", "foo");
        self::$store->set("foz", "moz");
        
        $this->assertTrue($this->getAdapter()->clear());
        
        $this->assertFalse(self::$store->get("bar"));
        $this->assertFalse(self::$store->get("foz"));
        $this->assertFalse(self::$store->get("foo"));
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Adapter\AdapterTest::getAdapter()
     */
    protected function getAdapter(): AdapterInterface
    {
        return new MemcachedAdapter(self::$store);
    }

}
