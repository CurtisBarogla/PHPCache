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
use Zoe\Component\Cache\Adapter\AdapterCollection;

/**
 * Get all methods for mocking AdapterInterface
 * 
 * @param CacheTestCase $case
 *   Cache test case
 * 
 * @return array
 *   All methods from AdapterInterface
 */
function getMethodsForAdapter(CacheTestCase $case): array
{
    $class = AdapterInterface::class;
    $reflection = new \ReflectionClass($class);
    
    return $case->reflection_extractMethods($reflection);
}

/**
 * AdapterCollection testcase
 * 
 * @see \Zoe\Component\Cache\Adapter\AdapterCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class AdapterCollectionTest extends CacheTestCase
{
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::add()
     */
    public function testAdd(): void
    {
        $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods(getMethodsForAdapter($this))->getMock();
        
        $collection = new AdapterCollection();
        $this->assertNull($collection->add("foo", $mock));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::get()
     */
    public function testGet(): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, ?string $return) use ($methods): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock->expects($this->exactly($callCount))->method("get")->with("foo")->will($this->returnValue($return));
            
            return $mock;
        };
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, null));
        $collection->add("bar", $adapter(1, "foo"));
        $collection->add("moz", $adapter(0, "foo"));
        
        $this->assertSame("foo", $collection->get("foo"));
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, null));
        $collection->add("bar", $adapter(1, null));
        
        $this->assertNull($collection->get("foo"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, array $keys, \Generator $return) use ($methods): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock
                ->expects($this->exactly($callCount))
                ->method("getMultiple")
                ->with($keys)
                ->will($this->returnValue($return));
            
            return $mock;
        };
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, ["foo", "bar", "moz"], $this->getGenerator(["foo" => "bar", "bar" => "foo", "moz" => null])));
        $collection->add("bar", $adapter(1, ["foo", "bar", "moz"], $this->getGenerator(["foo" => null,  "bar" => null,  "moz" => null])));
        $collection->add("moz", $adapter(1, ["foo", "bar", "moz"], $this->getGenerator(["foo" => "old", "bar" => "bar", "moz" => null])));
        
        $expected = $this->getGenerator(["foo" => "bar", "bar" => "foo", "moz" => null]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $collection->getMultiple(["foo", "bar", "moz"])));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::set()
     */
    public function testSet(): void
    {
        $this->doTestsBoolReturned("set", "foo", "bar", null);
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, array $values, array $return) use ($methods): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock
            ->expects($this->exactly($callCount))
            ->method("setMultiple")
            ->with($values)
            ->will($this->returnValue($return));
            
            return $mock;
        };
        $value = function(string $key, string $value, ?int $ttl): array {
            return [
                $key    =>  [
                    "value"     =>  $value,
                    "ttl"       =>  $ttl
                ]
            ];
        };
        
        $collection = new AdapterCollection();
        $collection->add(
            "foo", 
            $adapter(
            1, 
            [$value("foo", "bar", null), $value("bar", "foo", null), $value("moz", "poz", null)],
            ["foo" => true, "bar" => false, "moz" => false]));
        $collection->add(
            "bar",
            $adapter(
            1,
            [$value("bar", "foo", null), $value("moz", "poz", null)],
            ["bar" => true, "moz" => false]));
        $collection->add(
            "moz",
            $adapter(
            1,
            [$value("moz", "poz", null)],
            ["moz" => true]));
        
        $expected = ["foo" => true, "bar" => true, "moz" => true];
        
        $this->assertSame(
            $expected, 
            $collection->setMultiple([$value("foo", "bar", null), $value("bar", "foo", null), $value("moz", "poz", null)]));
        
        // early return
        
        $collection = new AdapterCollection();
        $collection->add(
            "foo",
            $adapter(
                1,
                [$value("foo", "bar", null), $value("bar", "foo", null), $value("moz", "poz", null)],
                ["foo" => true, "bar" => false, "moz" => false]));
        $collection->add(
            "bar",
            $adapter(
                1,
                [$value("bar", "foo", null), $value("moz", "poz", null)],
                ["bar" => true, "moz" => true]));
        $collection->add(
            "moz",
            $adapter(
                0,
                [$value("moz", "poz", null)],
                ["moz" => true]));
        
        $expected = ["foo" => true, "bar" => true, "moz" => true];
        
        $this->assertSame(
            $expected,
            $collection->setMultiple([$value("foo", "bar", null), $value("bar", "foo", null), $value("moz", "poz", null)]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::del()
     */
    public function testDel(): void
    {
        $this->doTestsBoolReturned("del", "foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::delMultiple()
     */
    public function testDelMultiple(): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, array $keys, array $return) use ($methods): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock
            ->expects($this->exactly($callCount))
            ->method("delMultiple")
            ->with($keys)
            ->will($this->returnValue($return));
            
            return $mock;
        };
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, ["foo", "bar", "moz"],  ["foo" => true,     "bar" => false, "moz" => false  ]));
        $collection->add("bar", $adapter(1, ["bar", "moz"],         ["bar" => false,    "moz" => true                   ]));
        $collection->add("moz", $adapter(1, ["bar"],                ["bar" => false                                     ]));
        
        $expected = ["foo" => true, "bar" => false, "moz" => true];
        
        $this->assertEquals($expected, $collection->delMultiple(["foo", "bar", "moz"]));
        
        // early return
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, ["foo", "bar", "moz"],  ["foo" => true,     "bar" => false, "moz" => false  ]));
        $collection->add("bar", $adapter(1, ["bar", "moz"],         ["bar" => true,     "moz" => true                   ]));
        $collection->add("moz", $adapter(0, ["bar"],                ["bar" => false                                     ]));
        
        $expected = ["foo" => true, "bar" => true, "moz" => true];
        
        $this->assertEquals($expected, $collection->delMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::exists()
     */
    public function testExists(): void
    {
        $this->doTestsBoolReturned("exists", "foo");
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterCollection::clear()
     */
    public function testClear(): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, bool $return) use ($methods): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock->expects($this->exactly($callCount))->method("clear")->with(null)->will($this->returnValue($return));
            
            return $mock;
        };
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, false));
        $collection->add("bar", $adapter(1, true));
        $collection->add("moz", $adapter(1, true));
        
        $this->assertFalse($collection->clear(null));
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, true));
        $collection->add("bar", $adapter(1, true));
        
        $this->assertTrue($collection->clear(null));
    }
    
    /**
     * Execute a test over simple method from an adapter
     * 
     * @param string $method
     *   Method of the adapter to test
     * @param mixed ...$args
     *   Args passed to the mocked adapter and the method called by the collection
     */
    private function doTestsBoolReturned(string $method, ...$args): void
    {
        $methods = getMethodsForAdapter($this);
        $adapter = function(int $callCount, bool $return) use ($methods, $method, $args): AdapterInterface {
            $mock = $this->getMockBuilder(AdapterInterface::class)->setMethods($methods)->getMock();
            $mock->expects($this->exactly($callCount))->method($method)->with(...$args)->will($this->returnValue($return));
            
            return $mock;
        };
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, false));
        $collection->add("bar", $adapter(1, true));
        $collection->add("moz", $adapter(0, true));
        
        $this->assertTrue($collection->{$method}(...$args));
        
        $collection = new AdapterCollection();
        $collection->add("foo", $adapter(1, false));
        $collection->add("bar", $adapter(1, false));
        
        $this->assertFalse($collection->{$method}(...$args));
    }
    
}
