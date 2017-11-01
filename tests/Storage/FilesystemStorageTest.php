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

use org\bovigo\vfs\vfsStream;
use Zoe\Component\Cache\Exception\InvalidRegexException;
use Zoe\Component\Cache\Storage\FilesystemStorage;
use Zoe\Component\Filesystem\Filesystem;
use Zoe\Component\Filesystem\FilesystemInterface;
use Zoe\Component\Filesystem\Exception\IOException;
use Zoe\Component\Filesystem\Exception\InvalidDirectoryException;
use Zoe\Component\Filesystem\Exception\InvalidFileException;
use Zoe\Component\Cache\Storage\StorageInterface;
use Zoe\Component\Internal\GeneratorTrait;

/**
 * FilesystemStorage testcase
 * 
 * @see \Zoe\Component\Cache\Storage\FilesystemStorage
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class FilesystemStorageTest extends StorageTest
{
    
    use GeneratorTrait;
    
    /**
     * Virtual directory
     * 
     * @var vfsStream
     */
    private $root;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->root = vfsStream::setup("/Cache");  
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::tearDown()
     */
    protected function tearDown(): void
    {
        unset($this->root);
    }
    
    /**
     * Set prefixed and non prefixed values
     * 
     * @param StorageInterface $store
     *   Store where to set the values
     * @param \ReflectionClass $reflection
     *   Reflection with store setted
     */
    private function setPrefixedAndNonPrefixedFixtures(StorageInterface $store, \ReflectionClass $reflection): void
    {
        $property = $reflection->getProperty("prefix");
        $property->setAccessible(true);
        $store->set("foo", "bar");
        $store->set("bar", "foo");
        $property->setValue($store, self::PREFIX);
        $store->set("foo", "bar");
        $store->set("bar", "foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::list()
     */
    public function testListOnlyWithValuesWithTheCurrentPrefix(): void
    {
        $root = vfsStream::setup("/Cache");
        
        $expected = $this->getGenerator([self::PREFIX."foo", self::PREFIX."bar"]);
        $store = new FilesystemStorage(new Filesystem(), $root->url());
        $reflection = new \ReflectionClass($store);
        $this->setPrefixedAndNonPrefixedFixtures($store, $reflection);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $store->list()));
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::count()
     */
    public function testCountOnlyTheKeysWithTheCurrentPrefix(): void
    {
        $root = vfsStream::setup("/Cache");
        
        $store = new FilesystemStorage(new Filesystem(), $root->url());
        $reflection = new \ReflectionClass($store);
        $this->setPrefixedAndNonPrefixedFixtures($store, $reflection);
        
        $this->assertSame(2, $store->count());
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::set()
     */
    public function testSetOnFail(): void
    {
        $filesystem = $this->mockExceptionOnFilesystem("put", new IOException());
        
        $store = new FilesystemStorage($filesystem, $this->root->url());
        $this->assertFalse($store->set("foo", "bar"));
        
        $filesystem = $this->mockExceptionOnFilesystem("put", new InvalidDirectoryException("foo"));
        
        $store = new FilesystemStorage($filesystem, $this->root->url());
        $this->assertFalse($store->set("foo", "bar"));
        
        $filesystem = $this->mockExceptionOnFilesystem("put", new InvalidFileException());
        
        $store = new FilesystemStorage($filesystem, $this->root->url());
        $this->assertFalse($store->set("foo", "bar"));
    }
    
    /**
     * Via 
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::isValid()
     * 
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::gc()
     */
    public function testGc(): void
    {
        $filesystem = $this->mockExceptionOnFilesystem("getMTime", new InvalidFileException());
        
        $store = new FilesystemStorage($filesystem, $this->root->url());
        
        $reflection = new \ReflectionClass($store);
        $method = $reflection->getMethod("isValid");
        $method->setAccessible(true);
        
        $this->assertFalse($method->invoke($store, "foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::flush()
     */
    public function testFlushOnFail(): void
    {
        $filesystem = $this->mockExceptionOnFilesystem("rmdirs", new IOException());
        
        $store = new FilesystemStorage($filesystem, $this->root->url());
        $this->assertFalse($store->flush());
    }
    
    /**_____EXCEPTIONS_____**/
    
    /**
     * @see \Zoe\Component\Cache\Storage\FilesystemStorage::list()
     */
    public function testExceptionListWhenPatternIsInvalid(): void
    {
        $this->expectException(InvalidRegexException::class);
        $this->expectExceptionMessage("This pattern '#' is invalid");
        
        $store = new FilesystemStorage(new Filesystem(), $this->root->url());
        $store->set("foo", "bar");
        
        $this->extractGenerator($store->list("#"));
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Storage\StorageTest::getStorages()
     */
    protected function getStorages(): array
    {
        return [
            "filesystem_store_without_prefix"   =>  new FilesystemStorage(new Filesystem(), $this->root->url()."/Cache"),
            "filesystem_store_without_prefix"   =>  new FilesystemStorage(new Filesystem(), $this->root->url(), self::PREFIX)
        ];  
    }
    
    /**
     * {@inheritDoc}
     * @see \ZoeTest\Component\Cache\Storage\StorageTest::getPrefixProperty()
     */
    protected function getPrefixProperty(): ?string
    {
        return "prefix";
    }
    
    /**
     * Mock an exception thrown by a method from the filesystem
     * 
     * @param \Throwable $exceptionClass
     *   Exception class thrown
     * @param string $method
     *   Method throwing the exception
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked exception
     */
    private function mockExceptionOnFilesystem(string $method, \Throwable $exceptionClass): \PHPUnit_Framework_MockObject_MockObject
    {
         $methods = \array_map(function(\ReflectionMethod $method) {
            return $method->getName();                    
         }, (new \ReflectionClass(FilesystemInterface::class))->getMethods());
         
         $mock = $this->getMockBuilder(FilesystemInterface::class)->setMethods($methods)->getMock();
         
         $mock->method($method)->will($this->throwException($exceptionClass));
         
         return $mock;
    }
    
}
