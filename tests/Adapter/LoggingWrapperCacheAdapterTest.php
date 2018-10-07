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
use Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter;
use Psr\Log\LoggerInterface;
use Ness\Component\Cache\Adapter\CacheAdapterInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LogLevel;
use Ness\Component\Cache\Adapter\LogAdapterLevel;

/**
 * LoggingWrapperCacheAdapter testcase
 * 
 * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter
 * 
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class LoggingWrapperCacheAdapterTest extends CacheTestCase
{
    
    /**
     * Current atom time
     * 
     * @var string
     */
    private $currentTime;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\interface_exists(LoggerInterface::class))
            self::markTestSkipped("No logger interface class found");
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->currentTime = (new \DateTime())->format(\DateTime::ATOM);
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::get()
     */
    public function testGet(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter->expects($this->exactly(3))->method("get")->withConsecutive(["foo"])->will($this->onConsecutiveCalls("bar", null, null));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : Cache key 'foo' cannot be reached over 'Wrapped' adapter|{$this->currentTime}");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertSame("bar", $adapter->get("foo"));
        $this->assertNull($adapter->get("foo"));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], "Wrapped", LogLevel::ERROR, LogAdapterLevel::LOG_GET);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertNull($adapter->get("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::getMultiple()
     */
    public function testGetMultiple(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("getMultiple")
                ->withConsecutive([["foo", "bar", "moz"]])
                ->will($this->onConsecutiveCalls(["bar", "foo", "poz"], [null, "foo", null], [null, "foo", null]));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : This keys 'foo, moz' via '{$adapterName}' adapter cannot be reached|{$this->currentTime}");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertSame(["bar", "foo", "poz"], $adapter->getMultiple(["foo", "bar", "moz"]));
        $this->assertSame([null, "foo", null], $adapter->getMultiple(["foo", "bar", "moz"]));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, LogAdapterLevel::LOG_GET);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertSame([null, "foo", null], $adapter->getMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::set()
     */
    public function testSet(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("set")
                ->withConsecutive(["foo", "bar", null])
                ->will($this->onConsecutiveCalls(true, false, false));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : This cache key 'foo' cannot be setted into cache via '{$adapterName}' adapter|{$this->currentTime}");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertTrue($adapter->set("foo", "bar", null));
        $this->assertFalse($adapter->set("foo", "bar", null));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, 0);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertFalse($adapter->set("foo", "bar", null));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::setMultiple()
     */
    public function testSetMultiple(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("setMultiple")
                ->withConsecutive([
                    [
                        "foo"  =>  ["value" => "bar", "ttl" => null],
                        "bar"  =>  ["value" => "foo", "ttl" => null],
                        "moz"  =>  ["value" => "poz", "ttl" => null]
                    ]
                ])
                ->will($this->onConsecutiveCalls(null, ["foo", "moz"], ["foo", "moz"]));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : This cache keys 'foo, moz' cannot be setted into cache via '{$adapterName}' adapter|{$this->currentTime}");
        };
        $values = [
            "foo"  =>  ["value" => "bar", "ttl" => null],
            "bar"  =>  ["value" => "foo", "ttl" => null],
            "moz"  =>  ["value" => "poz", "ttl" => null]
        ];
        $mocks = $this->getMocks($action);
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        $this->assertNull($adapter->setMultiple($values));
        $this->assertSame(["foo", "moz"], $adapter->setMultiple($values));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, 0);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertSame(["foo", "moz"], $adapter->setMultiple($values));

    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::delete()
     */
    public function testDelete(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("delete")
                ->withConsecutive(["foo"])
                ->will($this->onConsecutiveCalls(true, false, false));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : This cache key 'foo' cannot be deleted from cache via '{$adapterName}' adapter|{$this->currentTime}");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, LogAdapterLevel::LOG_DELETE);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertTrue($adapter->delete("foo"));
        $this->assertFalse($adapter->delete("foo"));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, 0);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertFalse($adapter->delete("foo"));

    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::deleteMultiple()
     */
    public function testDeleteMultiple(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter
                ->expects($this->exactly(3))
                ->method("deleteMultiple")
                ->withConsecutive([["foo", "bar", "moz"]])
                ->will($this->onConsecutiveCalls(null, ["foo", "moz"], ["foo", "moz"]));
            $logger
                ->expects($this->once())
                ->method("log")
                ->with(LogLevel::ERROR, "[ness/cache] : This cache keys 'foo, moz' cannot be deleted from cache via '{$adapterName}' adapter|{$this->currentTime}");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, LogAdapterLevel::LOG_DELETE);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertNull($adapter->deleteMultiple(["foo", "bar", "moz"]));
        $this->assertSame(["foo", "moz"], $adapter->deleteMultiple(["foo", "bar", "moz"]));
        
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"], null, LogLevel::ERROR, 0);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertSame(["foo", "moz"], $adapter->deleteMultiple(["foo", "bar", "moz"]));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::has()
     */
    public function testHas(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter->expects($this->exactly(2))->method("has")->withConsecutive(["foo"])->will($this->onConsecutiveCalls(true, false));
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertTrue($adapter->has("foo"));
        $this->assertFalse($adapter->has("foo"));
    }
    
    /**
     * @see \Ness\Component\Cache\Adapter\LoggingWrapperCacheAdapter::purge()
     */
    public function testPurge(): void
    {
        $action = function(MockObject $adapter, MockObject $logger, string $adapterName): void {
            $adapter->expects($this->once())->method("purge");
        };
        
        $mocks = $this->getMocks($action);
        $adapter = new LoggingWrapperCacheAdapter($mocks["adapter"]);
        $adapter->setLogger($mocks["logger"]);
        
        $this->assertNull($adapter->purge(null));
    }
    
        
    /**
     * Get mocked adapter and logger
     * 
     * @param \Closure $action
     *   Action done on the mocked cache adapter wrapped and the logger setted. 
     *   Takes as first parameter the adapter and as second the logger and third current classname of the mocked adapter
     * 
     * @return array
     *   Mocked adapter and logger
     */
    private function getMocks(?\Closure $action = null): array
    {
        $logger = $this->getMockBuilder(LoggerInterface::class)->getMock();
        $adapter = $this->getMockBuilder(CacheAdapterInterface::class)->getMock();
        
        if(null !== $action)
            $action->call($this, $adapter, $logger, \get_class($adapter));
        
        return [
            "adapter"   =>  $adapter,
            "logger"    =>  $logger
        ];
    }
    
}
