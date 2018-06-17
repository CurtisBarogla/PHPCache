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
use Ness\Component\Cache\Adapter\FilesystemCacheAdapter;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamContent;
use Ness\Component\Cache\Exception\CacheException;

/**
 * FilesystemCacheAdapter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemCacheAdapterTest extends CacheTestCase
{
    
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
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::get()
     */
    public function testGet(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $this->assertNull($adapter->get("foo"));
            $adapter->set("foo", "bar", 1);
            $this->assertSame("bar", $adapter->get("foo"));
            \sleep(2);
            $this->assertNull($adapter->get("foo"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $adapter->set("foo", "bar", 1);
            $adapter->set("bar", "foo", null);

            $this->assertSame(["bar", "foo"], $adapter->getMultiple(["foo", "bar"]));
            
            \sleep(2);
            
            $this->assertSame([null, "foo"], $adapter->getMultiple(["foo", "bar"]));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::set()
     */
    public function testSet(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $this->assertTrue($adapter->set("foo", "bar", null));
            $this->assertTrue($adapter->set("bar", "foo", 1));
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertTrue($adapter->has("bar"));
            
            \sleep(2);
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertFalse($adapter->has("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $this->assertNull($adapter->setMultiple(["foo" => ["value" => "bar", "ttl" => null], "bar" => ["value" => "foo", "ttl" => 1]]));
            
            $this->assertSame("bar", $adapter->get("foo"));
            $this->assertSame("foo", $adapter->get("bar"));
            
            \sleep(2);
            
            $this->assertSame("bar", $adapter->get("foo"));
            $this->assertNull($adapter->get("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $this->assertFalse($adapter->delete("foo"));
            $adapter->set("foo", "bar", 1);
            $adapter->set("bar", "foo", null);

            \sleep(2);
            
            $this->assertFalse($adapter->delete("foo"));
            $this->assertTrue($adapter->delete("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $adapter->set("foo", "bar", null);
            $adapter->set("bar", "foo", 1);
            
            $this->assertNull($adapter->deleteMultiple(["foo", "bar"]));
            
            $adapter->set("foo", "bar", null);
            $adapter->set("bar", "foo", 1);
            
            \sleep(2);
            
            $this->assertSame(["bar"], $adapter->deleteMultiple(["foo", "bar"]));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::has()
     */
    public function testHas(): void
    {
        $this->markAsLong();
        
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $this->assertFalse($adapter->has("foo"));
            $adapter->set("foo", "bar", 1);
            $adapter->set("bar", "foo", null);
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertTrue($adapter->has("bar"));
            
            \sleep(2);
            
            $this->assertFalse($adapter->has("foo"));
            $this->assertTrue($adapter->has("bar"));
        });
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $this->execute(function(FilesystemCacheAdapter $adapter, vfsStreamContent $stream, string $identifier): void {
            $adapter->set("foo", "bar", null);
            $adapter->set("bar", "foo", null);
            $adapter->set("FOO", "bar", null);
            $adapter->set("BAR", "foo", null);
            
            $this->assertNull($adapter->purge(null));
            
            foreach (["foo", "bar", "FOO", "BAR"] as $key) {
                $this->assertFalse($adapter->has($key));
            }
            
            $adapter->set("foo", "bar", null);
            $adapter->set("bar", "foo", null);
            $adapter->set("FOO", "bar", null);
            $adapter->set("BAR", "foo", null);
            
            $this->assertNull($adapter->purge("[A-Z]"));
            
            $this->assertTrue($adapter->has("foo"));
            $this->assertTrue($adapter->has("bar"));
            $this->assertFalse($adapter->has("FOO"));
            $this->assertFalse($adapter->has("BAR"));
        });
    }
    
                    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Ness\Component\Cache\Adapter\FilesystemCacheAdapter::__construct()
     */
    public function testExceptionWhenCacheDirectoryCannotBeInitialized(): void
    {
        $this->expectException(CacheException::class);
        $this->expectExceptionMessage("Cache directory 'vfs://foo/foo/bar' cannot be setted into 'vfs://foo/foo' directory");
        
        $stream = vfsStream::setup("foo", 0000);
        
        $adapter = new FilesystemCacheAdapter($stream->url()."/foo/bar");
    }
    
    /**
     * Execute a test over a prefixed and not prefix adapter
     * 
     * @param \Closure $test
     *   Test to execute
     */
    private function execute(\Closure $test): void
    {
        for ($i = 1; $i < 2; $i++) {
            $stream = vfsStream::setup();
            switch ($i) {
                case 1:
                    $identifier = "adapter_without_prefix";
                    $adapter = new FilesystemCacheAdapter($stream->url()."/var/cache");
                break;
                case 2:
                    $identifier = "adapter_with_prefix"; 
                    $adapter = new FilesystemCacheAdapter($stream->url()."/var/cache", "prefix");
                break;
            }
            $current = $stream->getChild("var")->getChild("cache");
            $current = ($identifier === "adapter_with_prefix") ? $current->getChild("prefix") : $current;
            $test->call($this, $adapter, $current, $identifier);
            unset($stream);
        }
    }
    
}
