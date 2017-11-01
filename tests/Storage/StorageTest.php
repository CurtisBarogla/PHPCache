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

namespace ZoeTest\Component\Cache\Storage;

use ZoeTest\Component\Cache\CacheTestCase;
use Zoe\Component\Cache\Storage\StorageInterface;
use Zoe\Component\Internal\GeneratorTrait;

/**
 * Storage testcase used by all storage testcases.
 * Test general usage for a store.
 * If you need more specifics scenarios, declare them into the dedicated class
 * 
 * \clearstatcache() is called before and after each fixtures setting to handling vfsStream on FilesystemStorage
 * 
 * @see \ZoeTest\Component\Cache\Storage\ArrayStorageTest
 * @see \ZoeTest\Component\Cache\Storage\FilesystemStorageTest
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class StorageTest extends CacheTestCase
{
    
    use GeneratorTrait;
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface
     */
    public function testInterface(): void
    {
        $interface = StorageInterface::class;
        $test = function(string $name, StorageInterface $store) use ($interface): void {
            $this->assertInstanceOf(StorageInterface::class, $store, "Store {$name} MUST be an instance of {$interface}");   
        };
        
        $this->execute($test);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::get()
     */
    public function testGet(): void
    {
        $testKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testKeyNotFound'");   
        };
        
        $testKeyFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $this->assertSame("bar", $store->get("foo"), "Store {$name} MUST return 'bar' on test : 'testKeyFound'");
        };
        
        $testKeyFoundButWillBeExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            $this->assertSame("bar", $store->get("foo"), "Store {$name} MUST return 'bar' on test : 'testKeyFoundButWillBeExpired'");
            \sleep(1);
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testKeyFoundButWillBeExpired'");
        };
        
        $this->execute($testKeyNotFound);
        $this->execute($testKeyFound);
        $this->execute($testKeyFoundButWillBeExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::set()
     */
    public function testSet(): void
    {
        $test = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertTrue($store->set("foo", "bar"), "Store {$name} MUST return true on test : 'testSet'");
            \clearstatcache();
        };
        
        $this->execute($test);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::setEx()
     */
    public function testSetEx(): void
    {
        $test = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertTrue($store->setEx("foo", 1, "bar"), "Store {$name} MUST return true on test : 'testSetEx'");
            \clearstatcache();
            $this->assertSame("bar", $store->get("foo"), "Store {$name} MUST return 'bar' on test : 'testSetEx'");
            \sleep(1);
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testSetEx'");
        };
        
        $this->execute($test, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::del()
     */
    public function testDel(): void
    {
        $testKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertFalse($store->del("foo"), "Store {$name} MUST return false on test : 'testKeyNotFound'");
        };
        
        $testKeyFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $this->assertTrue($store->del("foo"), "Store {$name} MUST return true on test : 'testKeyFound'");
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testKeyFound'");
        };
        
        $testKeyFoundButWillBeExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            \sleep(1);
            $this->assertFalse($store->del("foo"), "Store {$name} MUST return false on test : 'testKeyFoundButWillBeExpired'");
        };
        
        $this->execute($testKeyNotFound);
        $this->execute($testKeyFound);
        $this->execute($testKeyFoundButWillBeExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::del()
     */
    public function testExpire(): void
    {
        $testKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertFalse($store->expire("foo"), "Store {$name} MUST return false on test : 'testKeyNotFound'");
        };

        $testKeyFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $this->assertTrue($store->expire("foo"), "Store {$name} MUST return true on test : 'testKeyFound'");
            \clearstatcache();
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testKeyFound'");
        };
        
        $testKeyFoundButWillBeExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            \sleep(1);
            $this->assertFalse($store->expire("foo"), "Store {$name} MUST return false on test : 'testKeyFoundButWillBeExpired'");
        };
        
        $this->execute($testKeyNotFound);
        $this->execute($testKeyFound);
        $this->execute($testKeyFoundButWillBeExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::exists()
     */
    public function testExists(): void 
    {
        $testKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertFalse($store->exists("foo"), "Store {$name} MUST return false on test : 'testKeyNotFound'");
        };
        
        $testKeyFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $this->assertTrue($store->exists("foo"), "Store {$name} MUST return true on test : 'testKeyFound'");
        };
        
        $testKeyFoundButWillBeExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            \sleep(1);
            $this->assertFalse($store->exists("foo"), "Store {$name} MUST return false on test : 'testKeyFoundButWillBeExpired'");
        };
        
        $this->execute($testKeyNotFound);
        $this->execute($testKeyFound);
        $this->execute($testKeyFoundButWillBeExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::rename()
     */
    public function testRename(): void
    {
        $testRenameOk = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            
            $this->assertTrue($store->rename("foo", "bar"), "Store {$name} MUST return true on test : 'testRenameOk'");
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testRename'");
            $this->assertSame("bar", $store->get("bar"), "Store {$name} MUST return 'bar' on test : 'testRenameOk'");
        };
        
        $testSourceKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertFalse($store->rename("foo", "bar"), "Store {$name} MUST return false on test : 'testSourceKeyNotFound'");
        };
        
        $testTargetKeyFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->set("bar", "foo");
            \clearstatcache();
            $this->assertFalse($store->rename("foo", "bar"), "Store {$name} MUST return false on test : 'testTargetKeyFound'");
        };
        
        $testRenameWhenSourceKeyExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            
            \sleep(1);
            $this->assertFalse($store->rename("foo", "bar"), "Store {$name} MUST return false on test : 'testRenameWhenSourceKeyExpired'");
        };
        
        $testRenameWhenTargetKeyExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->setEx("bar", 1, "foo");
            \clearstatcache();
            
            \sleep(1);
            
            $this->assertTrue($store->rename("foo", "bar"), "Store {$name} MUST return true on test : 'testRenameWhenTargetKeyExpired'");
        };
        
        $this->execute($testRenameOk);
        $this->execute($testSourceKeyNotFound);
        $this->execute($testTargetKeyFound);
        $this->execute($testRenameWhenSourceKeyExpired, true);
        $this->execute($testRenameWhenTargetKeyExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::ttl()
     */
    public function testTtl(): void
    {
        $testKeyNotFound = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $this->assertSame(-1, $store->ttl("foo"), "Store {$name} MUST return 1 on test : 'testKeyNotFound'"); 
            \clearstatcache();
        };
        
        $testKeyPermanent = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            
            $this->assertNull($store->ttl("foo"), "Store {$name} MUST return null on test : 'testKeyPermanent'");
        };
        
        $testKeyNotPermanent = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            
            $this->assertSame(1, $store->ttl("foo"), "Store {$name} MUST return 1 on test : 'testKeyNotPermanent'");
        };
        
        $testKeyExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->setEx("foo", 1, "bar");
            \clearstatcache();
            $this->assertSame(1, $store->ttl("foo"), "Store {$name} MUST return 1 on test : 'testKeyExpired'");
            
            \sleep(1);
            
            $this->assertSame(-1, $store->ttl("foo"), "Store {$name} MUST return -1 on test : 'testKeyExpired'");
        };
        
        $this->execute($testKeyNotFound);
        $this->execute($testKeyPermanent);
        $this->execute($testKeyNotPermanent);
        $this->execute($testKeyExpired, true);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::flush()
     */
    public function testFlush(): void
    {
        $test = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->set("bar", "foo");
            \clearstatcache();
            
            $this->assertSame("bar", $store->get("foo"), "Store {$name} MUST return 'bar' on test : 'testFlush'");
            $this->assertSame("foo", $store->get("bar"), "Store {$name} MUST return 'foo' on test : 'testFlush'");
            \clearstatcache();
            $this->assertTrue($store->flush(), "Store {$name} MUST return true on test : 'testFlush'");
            \clearstatcache();
            $this->assertNull($store->get("foo"), "Store {$name} MUST return null on test : 'testFlush'");
            $this->assertNull($store->get("bar"), "Store {$name} MUST return null on test : 'testFlush'");
        };
        
        $this->execute($test);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::list()
     */
    public function testList(): void
    {        
        $testWithoutPattern = function(string $name, StorageInterface $store): void {
            $reflection = new \ReflectionClass($store);
            if(!$this->storeHasPrefix($store, $reflection)) {
                $expected = $this->getGenerator(["foo", "bar", "moz"]);
            } else {
                $expected = $this->getGenerator([self::PREFIX."foo", self::PREFIX."bar", self::PREFIX."moz"]);
            }  
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->set("bar", "foo");
            \clearstatcache();
            $store->set("moz", "poz");
            \clearstatcache();
            $this->assertTrue($this->assertGeneratorEquals($expected, $store->list()));
        };
        
        $testWithPattern = function(string $name, StorageInterface $store): void {
            $reflection = new \ReflectionClass($store);
            if(!$this->storeHasPrefix($store, $reflection)) {
                $expected = $this->getGenerator(["foo"]);
            } else {
                $expected = $this->getGenerator([self::PREFIX."foo"]);
            }
            
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->set("bar", "foo");
            \clearstatcache();
            $store->set("moz", "poz");
            \clearstatcache();
            
            $this->assertTrue($this->assertGeneratorEquals($expected, $store->list("fo")));
        };
        
        $this->execute($testWithoutPattern);
        $this->execute($testWithPattern);
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\StorageInterface::count()
     */
    public function testCount(): void
    {
        $test = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->set("bar", "foo");
            \clearstatcache();
            
            $this->assertSame(2, $store->count(), "Store {$name} MUST return 2 on test : 'testCount'");
        };
        
        $testWithExpired = function(string $name, StorageInterface $store): void {
            \clearstatcache();
            $store->set("foo", "bar");
            \clearstatcache();
            $store->setEx("bar", 1, "foo");
            \clearstatcache();
            
            $this->assertSame(2, $store->count(), "Store {$name} MUST return 2 on test : 'testWithExpired'");
            
            \sleep(1);
            
            $this->assertSame(1, $store->count(), "Store {$name} MUST return 1 on test : 'testWithExpired'");
        };
        
        $this->execute($test);
        $this->execute($testWithExpired, true);
    }
    
    
    /**
     * Test if the store has a prefix setted
     * 
     * @param StorageInterface $store
     *   Store to check
     * @param \ReflectionClass $reflection
     *   ReflectionClass instance setted with the store
     * 
     * @return bool
     *   True if the store has a prefix setted. False otherwise
     */
    protected function storeHasPrefix(StorageInterface $store, \ReflectionClass $reflection): bool
    {
        if(null === $this->getPrefixProperty()) return false;
        
        try {
            $property = $reflection->getProperty($this->getPrefixProperty());
            $property->setAccessible(true);
            if($property->getValue($store) !== null)
                return true;
            
            return false;
        } catch (\ReflectionException $e) {
            return false;
        }
    }
    
    /**
     * Execute a test over all storages setted.
     * Test take has as parameters the name of the storage currently in test, the instance of the storage.
     * 
     * @param callable $test
     *   Test to execute
     * @param bool $isLong
     *   Set to true if the test is implying usage of a slow function (e.g sleep())
     */
    private function execute(callable $test, bool $isLong = false): void
    {
         if($isLong && !self::EXECUTE_LONG_TESTS) {
             return;
         }
         
         foreach ($this->getStorages() as $name => $storage) {
             \call_user_func($test, $name, $storage);
         }
    }
    
    /**
     * Initialiaze and return all storages that must be tested
     * 
     * @return array
     *   StorageInterface to test
     */
    abstract protected function getStorages(): array;
    
    /**
     * If the store handle a prefix behaviour, get the name of the property containing the prefix.
     * Or null if not handled
     * 
     * @return string|null
     *   Property storing the prefix
     */
    abstract protected function getPrefixProperty(): ?string;
    
}
