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
use PHPUnit\Framework\MockObject\MockObject;
use Ness\Component\Cache\Adapter\CacheAdapterCollection;

/**
 * CacheAdapterCollection testcase
 * 
 * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class CacheAdapterCollectionTest extends CacheTestCase
{
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::get()
     */
    public function testGet(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("get")->withConsecutive(["foo"])->will($this->onConsecutiveCalls(null, null));  
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("get")->withConsecutive(["foo"])->will($this->onConsecutiveCalls("foo", null));  
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("get")->with("foo")->will($this->returnValue(null));
        });
        
        $adapter = new CacheAdapterCollection("Foo", $adapterFoo);
        $adapter->addAdapter("Bar", $adapterBar);
        $adapter->addAdapter("Moz", $adapterMoz);
        
        $this->assertSame("foo", $adapter->get("foo"));
        $this->assertNull($adapter->get("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("getMultiple")
                ->withConsecutive([ ["foo", "bar", "moz"] ])
                ->will($this->onConsecutiveCalls(["foo", "bar", "moz"], ["foo", "bar", null], [null, null, null]));
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(2))
                ->method("getMultiple")
                ->withConsecutive(
                    [ ["moz"] ],
                    [ ["foo", "bar", "moz"] ]
                )
                ->will($this->onConsecutiveCalls(["moz"], ["foo", "bar", null]));
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("getMultiple")->with(["moz"])->will($this->returnValue([null]));
        });
        
        $adapter = new CacheAdapterCollection("foo", $adapterFoo);
        $adapter->addAdapter("bar", $adapterBar);
        $adapter->addAdapter("moz", $adapterMoz);
        
        $this->assertSame(["foo", "bar", "moz"], $adapter->getMultiple(["foo", "bar", "moz"]));
        $this->assertSame(["foo", "bar", "moz"], $adapter->getMultiple(["foo", "bar", "moz"]));
        $this->assertSame(["foo", "bar", null], $adapter->getMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::set()
     */
    public function testSet(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar", null])->will($this->onConsecutiveCalls(false, false));
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar", null])->will($this->onConsecutiveCalls(true, false));
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("set")->withConsecutive(["foo", "bar", null])->will($this->returnValue(false, false));
        });
        
        $adapter = new CacheAdapterCollection("Foo", $adapterFoo);
        $adapter->addAdapter("Bar", $adapterBar);
        $adapter->addAdapter("Moz", $adapterMoz);
        
        $this->assertTrue($adapter->set("foo", "bar", null));
        $this->assertFalse($adapter->set("foo", "bar", null));        
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive([ [
                    "foo" => ["value" => "foo", "ttl" => null],
                    "bar" => ["value" => "foo", "ttl" => null],
                    "moz" => ["value" => "foo", "ttl" => null],
                ] ]
                )
                ->will($this->onConsecutiveCalls(
                    null,
                    ["foo", "bar"],
                    ["foo", "bar", "moz"])
                );
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive([ [
                    "foo" => ["value" => "foo", "ttl" => null],
                    "bar" => ["value" => "foo", "ttl" => null],
                    "moz" => ["value" => "foo", "ttl" => null],
                ] ]
                )
                ->will($this->onConsecutiveCalls(
                    ["foo", "bar", "moz"],
                    null,
                    ["moz"])
                );
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive([ [
                    "foo" => ["value" => "foo", "ttl" => null],
                    "bar" => ["value" => "foo", "ttl" => null],
                    "moz" => ["value" => "foo", "ttl" => null],
                ] ]
                )
                ->will($this->onConsecutiveCalls(
                    ["foo", "bar"],
                    ["foo", "bar"],
                    ["moz"])
                );
        });
                    
        $adapter = new CacheAdapterCollection("foo", $adapterFoo);
        $adapter->addAdapter("bar", $adapterBar);
        $adapter->addAdapter("moz", $adapterMoz);
        
        $this->assertNull($adapter->setMultiple([
            "foo" => ["value" => "foo", "ttl" => null],
            "bar" => ["value" => "foo", "ttl" => null],
            "moz" => ["value" => "foo", "ttl" => null],
        ]));
        $this->assertNull($adapter->setMultiple([
            "foo" => ["value" => "foo", "ttl" => null],
            "bar" => ["value" => "foo", "ttl" => null],
            "moz" => ["value" => "foo", "ttl" => null],
        ]));
        $this->assertSame(["moz"], $adapter->setMultiple([
            "foo" => ["value" => "foo", "ttl" => null],
            "bar" => ["value" => "foo", "ttl" => null],
            "moz" => ["value" => "foo", "ttl" => null],
        ]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::delete()
     */
    public function testDelete(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("delete")->with("foo")->will($this->returnValue(true));
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("delete")->with("foo")->will($this->returnValue(true));
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("delete")->with("foo")->will($this->returnValue(false));
        });
                    
        $adapter = new CacheAdapterCollection("Foo", $adapterFoo);
        $adapter->addAdapter("Bar", $adapterBar);
        $adapter->addAdapter("Moz", $adapterMoz);
        
        $this->assertTrue($adapter->delete("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("deleteMultiple")
                ->withConsecutive([ ["foo", "bar", "moz"] ])
                ->will($this->onConsecutiveCalls(
                    null, 
                    ["foo", "bar"], 
                    ["foo", "bar", "moz"])
                );
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("deleteMultiple")
                ->withConsecutive([ ["foo", "bar", "moz"] ])
                ->will($this->onConsecutiveCalls(
                    ["foo", "bar", "moz"], 
                    null, 
                    ["moz"])
                );
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("deleteMultiple")
                ->withConsecutive([ ["foo", "bar", "moz"] ])
                ->will($this->onConsecutiveCalls(
                    ["foo", "bar"], 
                    ["foo", "bar"], 
                    ["moz"])
                );
        });
                    
        $adapter = new CacheAdapterCollection("foo", $adapterFoo);
        $adapter->addAdapter("bar", $adapterBar);
        $adapter->addAdapter("moz", $adapterMoz);
                    
        $this->assertNull($adapter->deleteMultiple(["foo", "bar", "moz"]));
        $this->assertNull($adapter->deleteMultiple(["foo", "bar", "moz"]));
        $this->assertSame(["moz"], $adapter->deleteMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::has()
     */
    public function testHas(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("has")->withConsecutive(["foo"])->will($this->onConsecutiveCalls(false, false));
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->exactly(2))->method("has")->withConsecutive(["foo"])->will($this->onConsecutiveCalls(true, false));
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("has")->with("foo")->will($this->returnValue(false));
        });
                    
        $adapter = new CacheAdapterCollection("Foo", $adapterFoo);
        $adapter->addAdapter("Bar", $adapterBar);
        $adapter->addAdapter("Moz", $adapterMoz);
        
        $this->assertTrue($adapter->has("foo"));
        $this->assertFalse($adapter->has("foo"));        
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\CacheAdapterCollection::purge()
     */
    public function testPurge(): void
    {
        $adapterFoo = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(null);
        });
        $adapterBar = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(null);
        });
        $adapterMoz = $this->getMockedAdapter(function(MockObject $adapter, callable $prefixation): void {
            $adapter->expects($this->once())->method("purge")->with(null);
        });
            
        $adapter = new CacheAdapterCollection("Foo", $adapterFoo);
        $adapter->addAdapter("Bar", $adapterBar);
        $adapter->addAdapter("Moz", $adapterMoz);
        
        $this->assertNull($adapter->purge(null));
    }
    
}
