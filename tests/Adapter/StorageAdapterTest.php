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
use Zoe\Component\Cache\Adapter\AdapterInterface;
use Zoe\Component\Cache\Adapter\StorageAdapter;
use Zoe\Component\Cache\Storage\StorageInterface;

/**
 * StorageAdapter testcase
 * 
 * @see \Zoe\Component\Cache\Adapter\StorageAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class StorageAdapterTest extends CacheTestCase
{
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter
     */
    public function testInterface(): void
    {
        $adapter = $this->getAdapter($this->getMockedStorage());
        
        $this->assertInstanceOf(AdapterInterface::class, $adapter);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::get()
     */
    public function testGet(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("get")->with("foo")->will($this->returnValue("bar"));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertSame("bar", $adapter->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::get()
     */
    public function testGetWhenStoreHasNotTheValue(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("get")->with("foo")->will($this->returnValue(null));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertNull($adapter->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("get")->withConsecutive(["foo"], ["bar"])->willReturnOnConsecutiveCalls("bar", null);
        
        $adapter = $this->getAdapter($mock);
        
        $expected = $this->getGenerator(["foo" => "bar", "bar" => null]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $adapter->getMultiple(["foo", "bar"])));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::set()
     */
    public function testSet(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("set")->with("foo", "bar")->will($this->returnValue(true));
        $adapter = $this->getAdapter($mock);
        $this->assertTrue($adapter->set("foo", "bar", null));
        
        $mock = $this->getMockedStorage();
        $mock->method("setEx")->with("foo", 1, "bar")->will($this->returnValue(true));
        $adapter = $this->getAdapter($mock);
        $this->assertTrue($adapter->set("foo", "bar", 1));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::set()
     */
    public function testSetWithErrorFromTheStore(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("set")->with("foo", "bar")->will($this->returnValue(false));
        $adapter = $this->getAdapter($mock);
        $this->assertFalse($adapter->set("foo", "bar", null));
        
        $mock = $this->getMockedStorage();
        $mock->method("setEx")->with("foo", 1, "bar")->will($this->returnValue(false));
        $adapter = $this->getAdapter($mock);
        $this->assertFalse($adapter->set("foo", "bar", 1));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("set")->with("foo", "bar")->will($this->returnValue(true));
        $mock->method("setEx")->with("bar", 1, "foo")->will($this->returnValue(true));
        
        $adapter = $this->getAdapter($mock);
        $items = [
            "foo" => ["value" => "bar", "ttl" => null],
            "bar" => ["value" => "foo", "ttl" => 1],
        ];
        $expected = ["foo" => true, "bar" => true];
        $this->assertSame($expected, $adapter->setMultiple($items));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::setMultiple()
     */
    public function testSetMultipleWithError(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("setEx")->with("bar", 1, "foo")->will($this->returnValue(false));
        $mock->method("set")->with("foo", "bar")->will($this->returnValue(true));
        
        $adapter = $this->getAdapter($mock);
        $items = [
            "foo" => ["value" => "bar", "ttl" => null],
            "bar" => ["value" => "foo", "ttl" => 1],
        ];
        $expected = ["foo" => true, "bar" => false];
        $this->assertSame($expected, $adapter->setMultiple($items));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::del()
     */
    public function testDel(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("del")->with("foo")->will($this->returnValue(true));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertTrue($adapter->del("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::del()
     */
    public function testDelWhenStoreReturnFalse(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("del")->with("foo")->will($this->returnValue(false));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertFalse($adapter->del("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::del()
     */
    public function testDelMultiple(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("del")->withConsecutive(["foo"], ["bar"])->willReturnOnConsecutiveCalls(true, true);
        
        $adapter = $this->getAdapter($mock);
        $expected = ["foo" => true, "bar" => true];
        $this->assertSame($expected, $adapter->delMultiple(["foo", "bar"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::del()
     */
    public function testDelMultipleWithError(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("del")->withConsecutive(["foo"], ["bar"], ["poz"])->willReturnOnConsecutiveCalls(true, false, true);
        
        $adapter = $this->getAdapter($mock);
        $expected = ["foo" => true, "bar" => false, "poz" => true];
        $this->assertSame($expected, $adapter->delMultiple(["foo", "bar", "poz"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::exists()
     */
    public function testExists(): void
    {
        $mock = $this->getMockedStorage();
        $mock->method("exists")->with("foo")->will($this->returnValue(true));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertTrue($adapter->exists("foo"));
        
        $mock = $this->getMockedStorage();
        $mock->method("exists")->with("foo")->will($this->returnValue(false));
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertFalse($adapter->exists("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::clear()
     */
    public function testClearWithoutPattern(): void
    {
        $mock = $this->getMockedStorage();
        
        $mock->method("list")->with(null)->will($this->returnValue(self::getGenerator(["foo", "bar"])));
        $mock->method("del")->withConsecutive(["foo"], ["bar"])->willReturnOnConsecutiveCalls(true, true);
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertTrue($adapter->clear());
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::clear()
     */
    public function testClearWithoutPatternWithError(): void
    {
        $mock = $this->getMockedStorage();
        
        $mock->method("list")->with(null)->will($this->returnValue(self::getGenerator(["foo", "bar"])));
        $mock->method("del")->withConsecutive(["foo"], ["bar"])->willReturnOnConsecutiveCalls(true, false);
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertFalse($adapter->clear());
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::clear()
     */
    public function testClearWithPattern(): void
    {
        $mock = $this->getMockedStorage();
        
        $mock->method("list")->with("fo")->will($this->returnValue(self::getGenerator(["foo", "foz"])));
        $mock->method("del")->withConsecutive(["foo"], ["foz"])->willReturnOnConsecutiveCalls(true, true);
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertTrue($adapter->clear("fo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\StorageAdapter::clear()
     */
    public function testClearWithPatternWithError(): void
    {
        $mock = $this->getMockedStorage();
        
        $mock->method("list")->with("fo")->will($this->returnValue(self::getGenerator(["foo", "foz"])));
        $mock->method("del")->withConsecutive(["foo"], ["foz"])->willReturnOnConsecutiveCalls(true, false);
        
        $adapter = $this->getAdapter($mock);
        
        $this->assertFalse($adapter->clear("fo"));
    }
    
    /**
     * Get an instance of the StorageAdapter
     * 
     * @return AdapterInterface
     *   Storage adapter
     */
    private function getAdapter(StorageInterface $mockedStorage): StorageAdapter
    {
        return new StorageAdapter($mockedStorage);
    }
    
    /**
     * Get an instance of a mocked StorageInterface
     * 
     * @return \PHPUnit_Framework_MockObject_MockObject
     *   Mocked instance of StorageInterface
     */
    private function getMockedStorage(): \PHPUnit_Framework_MockObject_MockObject
    {
        $reflection = new \ReflectionClass(StorageInterface::class);
        $methods = $this->reflection_extractMethods($reflection);
        
        $mock = $this->getMockBuilder(StorageInterface::class)->disableOriginalClone()->setMethods($methods)->getMock();
        
        return $mock;
    }
    
}
