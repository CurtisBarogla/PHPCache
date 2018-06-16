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
use Ness\Component\Cache\Adapter\InMemoryCacheAdapter;

/**
 * InMemoryCacheAdapter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class InMemoryCacheAdapterTest extends CacheTestCase
{

    /**
     * Adapters initialized
     * 
     * @var InMemoryCacheAdapter[]
     */
    private $adapter;
    
    /**
     * If the test MUST be entierely skipped no matter what
     * 
     * @var bool
     */
    private const SKIP = false;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        if(self::SKIP)
            $this->markTestSkipped("Test " . __CLASS__. " skipped");
        
        $this->adapter = new InMemoryCacheAdapter();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        unset($this->adapter);
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::get()
     */
    public function testGet(): void
    {
        $this->markAsLong();
        
        $this->inject("foo:bar:-1, bar:foo:1");
        $this->assertSame("bar", $this->adapter->get("foo"));
        $this->assertSame("foo", $this->adapter->get("bar"));
        $this->assertNull($this->adapter->get("moz"));
        
        \sleep(2);
        
        $this->assertSame("bar", $this->adapter->get("foo"));
        $this->assertNull($this->adapter->get("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $this->markAsLong();
        
        $this->inject("foo:bar:-1, bar:foo:1");
        $this->assertSame(["bar", "foo", null], $this->adapter->getMultiple(["foo", "bar", "moz"]));
        
        \sleep(2);
        
        $this->assertSame(["bar", null, null], $this->adapter->getMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::set()
     */
    public function testSet(): void
    {
        $this->assertTrue($this->adapter->set("foo", "bar", null));
        $this->assertTrue($this->adapter->set("bar", "foo", 1));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $this->markAsLong();

        $this->assertNull($this->adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => 1]]));
        
        $this->assertSame("bar", $this->adapter->get("foo"));
        $this->assertSame("foo", $this->adapter->get("bar"));
        
        \sleep(2);
        
        $this->assertSame("bar", $this->adapter->get("foo"));
        $this->assertNull($this->adapter->get("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $this->markAsLong();
        
        $this->inject("foo:bar:-1, bar:foo:1");
        $this->assertTrue($this->adapter->delete("foo"));
        $this->assertFalse($this->adapter->delete("foo"));
        
        \sleep(2);
        
        $this->assertFalse($this->adapter->delete("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $this->markAsLong();

        $keys = ["foo", "bar", "moz", "poz"];
        $values = "foo:bar:-1, bar:foo:1, moz:poz:-1, poz:moz:1";
        $this->inject($values);
        
        $this->assertNull($this->adapter->deleteMultiple($keys));
        foreach ($keys as $key)
            $this->assertNull($this->adapter->get($key));
        
        $this->inject($values);
        \sleep(2);
        
        $this->assertSame(["bar", "poz"], $this->adapter->deleteMultiple($keys));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::has()
     */
    public function testHas(): void
    {
        $this->markAsLong();

        $this->inject("foo:bar:-1, bar:foo:1");
        $this->assertTrue($this->adapter->has("foo"));
        $this->assertFalse($this->adapter->has("moz"));
        
        \sleep(2);
        
        $this->assertTrue($this->adapter->has("foo"));
        $this->assertFalse($this->adapter->has("bar"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\InMemoryCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $values = "foo:bar:-1, BAR:foo:-1, MOZ:poz:-1, poz:moz:-1";
        $this->inject($values);
        $this->assertNull($this->adapter->purge(null));
        
        foreach (["foo", "BAR", "MOZ", "poz"] as $key)
            $this->assertNull($this->adapter->get($key));
        
        $this->inject($values);
        $this->assertNull($this->adapter->purge("[A-Z]+"));
        
        $this->assertSame("bar", $this->adapter->get("foo"));
        $this->assertSame("moz", $this->adapter->get("poz"));
        $this->assertNull($this->adapter->get("BAR"));
        $this->assertNull($this->adapter->get("MOZ"));
    }
    
    /**
     * Inject values into the adapter.
     * (foo:bar:-1) => key = foo, value = var, ttl = null
     * (foo:bar:20) => key = foo, value = var, ttl = 20
     * 
     * @param string $values
     *   Values respecting pattern
     */
    private function inject(string $values): void
    {
        foreach (\explode(", ", $values) as $value) {
            list ($key, $value, $ttl) = \explode(":", $value);
            $ttl = ("-1" === $ttl) ? null : (int)$ttl;
            $this->adapter->set($key, $value, $ttl);
        }
    }
    
}
