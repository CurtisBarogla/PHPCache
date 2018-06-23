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

use Ness\Component\Cache\MemcachedCache;
use Psr\Log\LoggerInterface;
use function NessTest\Component\Cache\config\getTestConfiguration;

/**
 * MemcachedCache testcase
 *
 * @see \Ness\Component\Cache\MemcachedCache
 *
 * @author CurtisBarogla <curtis_barogla@outlook.fr>
 *
 */
class MemcachedCacheTest extends AbstractCacheTest
{
    
    /**
     * Memcached instance
     *
     * @var \Memcached
     */
    private static $memcached;
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUpBeforeClass()
     */
    public static function setUpBeforeClass(): void
    {
        if(!\class_exists(\Memcached::class))
            self::markTestSkipped("Memcached class not found");
        
        $config = getTestConfiguration("MEMCACHED_CONFIGS")["memcached_without_prefix"];
        self::$memcached = new \Memcached();
        self::$memcached->addServer($config["host"], $config["port"]);
    }
    
    /**
     * {@inheritDoc}
     * @see \PHPUnit\Framework\TestCase::setUp()
     */
    protected function setUp(): void
    {
        $this->cache = [
            new MemcachedCache(self::$memcached)
        ];
        if(\interface_exists(LoggerInterface::class))
            $this->cache[] = new MemcachedCache(self::$memcached, null, null, $this->getMockBuilder(LoggerInterface::class)->getMock());
    }
    
}