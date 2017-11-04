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
use Zoe\Component\Internal\GeneratorTrait;

/**
 * Common class for test cases implying usage of extra storage mechanism (e.g redis, memcached etc...)
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
abstract class AdapterTest extends CacheTestCase
{
    
    use GeneratorTrait;
    
    /**
     * Store setted into the adapter
     * 
     * @var mixed
     */
    protected static $store;
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface
     */
    public function testInterface(): void
    {
        $this->assertInstanceOf(AdapterInterface::class, $this->getAdapter());  
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function testGetWhenValid(): void
    {
        $this->assertSame(
            "bar", 
            $this->getAdapter()->get("foo"), 
            $this->getMessageForMissingFixture());  
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::get()
     */
    public function testGetWhenInvalid(): void
    {
        $this->assertNull($this->getAdapter()->get("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $expected = $this->getGenerator(["foo" => "bar", "bar" => null]);
        
        $this->assertTrue($this->assertGeneratorEquals($expected, $this->getAdapter()->getMultiple(["foo", "bar"])));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::set()
     */
    public function testSet(): void
    {
        $this->assertTrue($this->getAdapter()->set("foo", "bar", null));
        $this->assertTrue($this->getAdapter()->set("foo", "bar", 7));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $values = [
            "foo"   =>  [
                "value"     =>  "bar",
                "ttl"       =>  null
            ],
            "bar"   =>  [
                "value"     =>  "foo",
                "ttl"       =>  7
            ]
        ];
        $expected = ["foo" => true, "bar" => true];
        
        $this->assertSame($expected, $this->getAdapter()->setMultiple($values));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::del()
     */
    public function testDel(): void
    {
        $this->assertTrue($this->getAdapter()->del("foo"), $this->getMessageForMissingFixture());
        $this->assertFalse($this->getAdapter()->del("bar"));
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::delMultiple()
     */
    public function testDelMultiple(): void
    {
        $expected = ["foo" => true, "bar" => false];
        
        $this->assertSame($expected, $this->getAdapter()->delMultiple(["foo", "bar"]), $this->getMessageForMissingFixture());
    }
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::exists()
     */
    public function testExists(): void
    {
        $this->assertTrue($this->getAdapter()->exists("foo"), $this->getMessageForMissingFixture());
        $this->assertFalse($this->getAdapter()->exists("bar"));
    }
    
    /**
     * Get the message when the testing fixture is missing
     * 
     * @param string $key
     *   Key fixture
     * @param string $value
     *   Value fixture
     * 
     * @return string
     *   Message
     */
    private function getMessageForMissingFixture(string $key = "foo", string $value = "bar"): string
    {
        return "A '{$key}' key with a '{$value}' value MUST be setted into the store of the tested adapter";
    }
    
    /**
     * Get the adapter instance currently in test
     * 
     * @return AdapterInterface
     *   Adapter instance
     */
    abstract protected function getAdapter(): AdapterInterface;
    
    /**
     * @see \Zoe\Component\Cache\Adapter\AdapterInterface::clear()
     */
    abstract public function testClear(): void;
    
}
