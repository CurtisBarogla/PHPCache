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

namespace Ness\Component\Cache\Adapter {
    global $fails;
    
    /**
     * Initialize keys than will return false on the next call of apcu_store
     * 
     * @param array $keys
     *   Keys to set to errors
     */
    function init(array $keys): void
    {
        global $fails;
        $fails = $keys;
    }
    
    /**
     * {@inheritdoc}
     */
    function apcu_store($key, $var, $ttl)
    {
        global $fails;          
        if(\in_array($key, $fails)) 
            return false;
        
        return \apcu_store($key, $var, $ttl);
    }
    
    /**
     * Reset global errors array
     */
    function reset(): void
    {
        global $fails;
        $fails = [];
    }
};

namespace NessTest\Component\Cache\Adapter {

    use NessTest\Component\Cache\CacheTestCase;
    use Ness\Component\Cache\Adapter\ApcuCacheAdapter;
    use function Ness\Component\Cache\Adapter\init;
    use function Ness\Component\Cache\Adapter\reset;
                                                    
    /**
     * ApcuCacheAdapter testcase
     * 
     * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter
     * 
     * @author CurtisBarogla <curtis_barogla@outlook.fr>
     *
     */
    class ApcuCacheAdapterTest extends CacheTestCase
    {
        
        /**
         * {@inheritDoc}
         * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
         */
        public static function setUpBeforeClass(): void
        {
            if(!\extension_loaded("apcu") || \ini_get("apc.enable_cli") === "0")
                self::markTestSkipped("Apcu extension not loaded or not enabled in cli mode. Test skipped");
        }
        
        /**
         * {@inheritDoc}
         * @see \PHPUnit\Framework\TestCase::tearDown()
         */
        protected function tearDown(): void
        {
            \apcu_clear_cache();
            reset();
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::get()
         */
        public function testGet(): void
        {
            \apcu_store("foo", "bar");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertSame("bar", $adapter->get("foo"));
            $this->assertNull($adapter->get("bar"));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::getMultiple()
         */
        public function testGetMultiple(): void
        {
            \apcu_store("foo", "bar");
            \apcu_store("bar", "foo");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertSame(["bar", "foo", null], $adapter->getMultiple(["foo", "bar", "moz"]));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::set()
         */
        public function testSet(): void
        {
            init(["bar"]);
            $adapter = new ApcuCacheAdapter();
            
            $this->assertTrue($adapter->set("foo", "bar", null));
            $this->assertFalse($adapter->set("bar", "foo", null));
            
            $this->assertSame("bar", \apcu_fetch("foo"));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::setMultiple()
         */
        public function testSetMultiple(): void
        {
            $adapter = new ApcuCacheAdapter();
            
            $this->assertNull($adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => null]]));
            
            $this->assertSame("bar", \apcu_fetch("foo"));
            $this->assertSame("foo", \apcu_fetch("bar"));
            
            init(["foo"]);
            
            $this->assertSame(["foo"], $adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => null]]));
            $this->assertSame("foo", \apcu_fetch("bar"));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::delete()
         */
        public function testDelete(): void
        {
            \apcu_store("foo", "bar");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertTrue($adapter->delete("foo"));
            $this->assertFalse($adapter->delete("foo"));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::deleteMultiple()
         */
        public function testDeleteMultiple(): void
        {
            \apcu_store("foo", "bar");
            \apcu_store("bar", "foo");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertNull($adapter->deleteMultiple(["foo", "bar"]));
            $this->assertSame(["foo", "bar"], $adapter->deleteMultiple(["foo", "bar"]));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::has()
         */
        public function testHas(): void
        {
            \apcu_store("foo", "bar");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertFalse($adapter->has("bar"));
        }
        
        /**
         * @see \Ness\Component\Cache\Adapter\ApcuCacheAdapter::purge()
         */
        public function testPurge(): void
        {
            \apcu_store("foo_foo", "bar");
            \apcu_store("bar_foo", "bar");
            \apcu_store("foo_bar", "bar");
            \apcu_store("bar_bar", "bar");
            
            $adapter = new ApcuCacheAdapter();
            
            $this->assertNull($adapter->purge(null));
            
            foreach (["foo_foo", "bar_foo", "foo_bar", "bar_bar"] as $key)
                $this->assertFalse($adapter->has($key));
            
            \apcu_store("foo_foo", "bar");
            \apcu_store("bar_foo", "bar");
            \apcu_store("foo_bar", "bar");
            \apcu_store("bar_bar", "bar");
            
            $this->assertNull($adapter->purge("foo_"));
            
            foreach (["foo_foo", "foo_bar"] as $key)
                $this->assertFalse($adapter->has($key));
            foreach (["bar_foo", "bar_bar"] as $key)
                $this->assertTrue($adapter->has($key));
        }
        
    }
    
};
