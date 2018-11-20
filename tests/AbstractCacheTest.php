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

namespace NessTest\Component\Cache;

use Ness\Component\Cache\AbstractCache;
use Psr\SimpleCache\CacheInterface;
use Psr\Cache\CacheItemPoolInterface;
use Ness\Component\Cache\NullCache;
use Ness\Component\Cache\PSR16\Cache;
use Ness\Component\Cache\PSR6\CacheItemPool;
use Ness\Component\Cache\Exception\CacheException;

/**
 * Common to all caches
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AbstractCacheTest extends CacheTestCase
{

    /**
     * Instance of cache
     * 
     * @var AbstractCache[]
     */
    protected $cache;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        CacheItemPool::unregisterSerializer();
        Cache::unregisterSerializer();
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $pool->commit();
        });
        CacheItemPool::unregisterSerializer();
        Cache::unregisterSerializer();
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::get()
     */
    public function testGet(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            $cache->set("foo", "bar");
            
            if($cache instanceof NullCache) {
                $this->assertNull($cache->get("foo"));
            } else {
                $this->assertSame("bar", $cache->get("foo"));
                $this->assertSame("default", $cache->get("bar", "default"));
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::set()
     */
    public function testSet(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if($cache instanceof NullCache) {
                $this->assertFalse($cache->set("foo", "bar"));
            } elseif($tagSupport) {
                $this->assertTrue($cache->set("foo", "bar", -1, ["foo", "bar"]));
            } else {
                $this->assertTrue($cache->set("foo", "bar"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::delete()
     */
    public function testDelete(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            $cache->set("foo", "bar");
            
            if($cache instanceof NullCache) {
                $this->assertFalse($cache->delete("foo"));
            } else {    
                $this->assertTrue($cache->delete("foo"));
                $this->assertFalse($cache->delete("bar"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::clear()
     */
    public function testClear(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if($tagSupport) {
                $cache->set("foo", "bar", null, ["foo", "bar"]);
                $cache->save($cache->getItem("foo")->set("bar")->setTags(["foo", "bar"]));
                
                $this->assertTrue($cache->clear());
            } else {
                $this->assertTrue($cache->clear());
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            $cache->set("moz", "poz");
            $cache->set("poz", "moz");
            
            if($cache instanceof NullCache) {
                $this->assertSame(["foo" => "default", "bar" => "default"], $cache->getMultiple(["foo", "bar"], "default"));   
            } else {
                $found = $cache->getMultiple(["foo", "poz", "moz"], "default");
                
                $this->assertSame("poz", $found["moz"]);
                $this->assertSame("moz", $found["poz"]);
                $this->assertSame("default", $found["foo"]);
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if($cache instanceof NullCache) {
                $this->assertFalse($cache->setMultiple(["foo" => "bar", "bar" => "foo"]));
            } elseif($tagSupport) {
                $this->assertTrue($cache->setMultiple(["foo" => "bar", "bar" => "foo"], -1, ["foo", "bar"]));
                $this->assertSame("bar", $cache->get("foo"));
            } else {
                $this->assertTrue($cache->setMultiple(["foo" => "bar", "bar" => "foo"]));
                $this->assertSame("bar", $cache->get("foo"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            $cache->set("foo", "bar");
            $cache->set("bar", "foo");
            
            if($cache instanceof NullCache) {
                $this->assertFalse($cache->deleteMultiple(["foo", "bar"]));
            } else {
                $this->assertTrue($cache->deleteMultiple(["foo", "bar"]));
                $this->assertFalse($cache->deleteMultiple(["foo", "bar"]));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::has()
     */
    public function testHas(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            $cache->set("foo", "bar");
            
            if($cache instanceof NullCache) {
                $this->assertFalse($cache->has("foo"));
            } else {
                $this->assertTrue($cache->has("foo"));
                $this->assertFalse($cache->has("bar"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::getItem()
     */
    public function testGetItem(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $item = $pool->getItem("foo");
            
            $this->assertSame("foo", $item->getKey());
            $this->assertFalse($item->isHit());
            
            $pool->save($item);
            
            $item = $pool->getItem("foo");
            
            if($pool instanceof NullCache) {
                $this->assertSame("foo", $item->getKey());
                $this->assertFalse($item->isHit());
            } else {
                $this->assertSame("foo", $item->getKey());
                $this->assertTrue($item->isHit());                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::getItems()
     */
    public function testGetItems(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $items = $pool->getItems(["foo", "bar"]);
            foreach ($items as $item)
                $this->assertFalse($item->isHit());
            $pool->save($items["foo"]);
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->getItem("foo")->isHit());
            } else {
                $this->assertTrue($pool->getItem("foo")->isHit());                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::hasItem()
     */
    public function testHasItem(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $this->assertFalse($pool->hasItem("foo"));
            $pool->save($pool->getItem("foo"));
            
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->hasItem("foo"));
            } else {                
                $this->assertTrue($pool->hasItem("foo"));
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::deleteItem()
     */
    public function testDeleteItem(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $this->assertFalse($pool->deleteItem("foo"));
            $pool->save($pool->getItem("foo"));
            
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->deleteItem("foo"));
            } else {
                $this->assertTrue($pool->deleteItem("foo"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::deleteItems()
     */
    public function testDeleteItems(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $pool->save($pool->getItem("foo"));
            $pool->save($pool->getItem("bar"));
            
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->deleteItems(["foo", "bar"]));
            } else {
                $this->assertTrue($pool->deleteItems(["foo", "bar"]));
                $this->assertFalse($pool->deleteItems(["foo", "bar"]));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::save()
     */
    public function testSave(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->save($pool->getItem("foo")));
                $this->assertFalse($pool->hasItem("foo"));
            } elseif($tagSupport) {
                $this->assertTrue($pool->save($pool->getItem("foo")->setTags(["foo", "bar"])));
                $this->assertTrue($pool->hasItem("foo"));   
            } else {
                $this->assertTrue($pool->save($pool->getItem("foo")));
                $this->assertTrue($pool->hasItem("foo"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::saveDeferred()
     */
    public function testSaveDeferred(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $this->assertTrue($pool->saveDeferred($pool->getItem("foo")));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::commit()
     */
    public function testCommit(): void
    {
        $this->execute(function(CacheItemPoolInterface $pool, bool $tagSupport): void {
            $pool->saveDeferred($pool->getItem("foo"));
            
            if($pool instanceof NullCache) {
                $this->assertFalse($pool->commit());
                
                $this->assertFalse($pool->hasItem("foo"));
            } else {
                $this->assertTrue($pool->commit());
                
                $this->assertTrue($pool->hasItem("foo"));                
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::invalidateTag()
     */
    public function testInvalidateTag(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if($cache instanceof NullCache)
                $this->assertTrue(true);
            if($tagSupport) {
                $cache->set("foo", "bar", -1, ["foo", "bar"]);
                $this->assertTrue($cache->invalidateTag("foo"));
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::invalidateTags()
     */
    public function testInvalidateTags(): void
    {
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if($cache instanceof NullCache)
                $this->assertTrue(true);
            if($tagSupport) {
                $cache->set("foo", "bar", -1, ["foo", "bar"]);
                $this->assertTrue($cache->invalidateTags(["foo", "bar"]));
            }
        });
    }
    
    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::invalidateTag()
     */
    public function testExceptionInvalidateTagWhenNotSupportingTag(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage("Cannot invalidate tag as this cache does not support tagging");
        
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if(!$tagSupport) {
                $cache->invalidateTag(["foo", "bar"]);
            }
        });
    }
    
    /**
     * @see \Ness\Component\Cache\AbstractCache::invalidateTags()
     */
    public function testExceptionInvalidateTagsWhenNotSupportingTag(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage("Cannot invalidate tag as this cache does not support tagging");
        
        $this->execute(function(CacheInterface $cache, bool $tagSupport): void {
            if(!$tagSupport) {
                $cache->invalidateTags(["foo", "bar"]);
            }
        });
    }
    
    /**
     * Execute an action over all setted caches.
     * 
     * @param \Closure $action
     *   Action to execute. Takes as parameter the cache
     */
    private function execute(\Closure $action): void
    {
        foreach ($this->cache as $cache) {
            $reflection = new \ReflectionClass($cache);
            $property = $reflection->getProperty("taggable");
            $property->setAccessible(true);
            $action->call($this, $cache, $property->getValue($cache));
            $cache->clear();
        }
    }
    
}
