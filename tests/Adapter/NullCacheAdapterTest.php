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
use Ness\Component\Cache\Adapter\NullCacheAdapter;

/**
 * NullCacheAdapter testcase
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class NullCacheAdapterTest extends CacheTestCase
{
 
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::get()
     */
    public function testGet(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertNull($adapter->get("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertSame([null, null], $adapter->getMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::set()
     */
    public function testSet(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertFalse($adapter->set("foo", "bar", null));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
         $adapter = new NullCacheAdapter();
         
         $this->assertSame(["foo", "bar"], $adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => null]]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertFalse($adapter->delete("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertSame(["foo", "bar"], $adapter->deleteMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::has()
     */
    public function testHas(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertFalse($adapter->has("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\NullCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $adapter = new NullCacheAdapter();
        
        $this->assertNull($adapter->purge(null));
    }
    
}
